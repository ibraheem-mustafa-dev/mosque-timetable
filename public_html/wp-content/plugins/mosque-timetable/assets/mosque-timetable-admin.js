/**
 * Mosque Timetable Admin JavaScript (Modern-only)
 * Removes all legacy selectors/paths.
 * Version: 3.1.0-modern
 */

/* eslint-env browser, jquery */
(function ($) {
  'use strict';

  // ---- Admin object (modern-only) ----
  window.MosqueTimetableAdmin = {
    config: {
      ajaxUrl: (typeof mosqueTimetableAdmin !== 'undefined' && mosqueTimetableAdmin.ajaxUrl) || '/wp-admin/admin-ajax.php',
      nonce: (typeof mosqueTimetableAdmin !== 'undefined' && mosqueTimetableAdmin.nonce) || '',
      strings: (typeof mosqueTimetableAdmin !== 'undefined' && mosqueTimetableAdmin.strings) || {},
      currentMonth: 1,
      currentYear: new Date().getFullYear(),
      unsavedChanges: false,
      autoSaveTimer: null
    },

    t(key, fallback) {
      const dict = this.config.strings || {};
      return (dict[key] && String(dict[key])) || fallback || key;
    },

    // ---------------- Init ----------------
    init() {
      this.initMonthTabs();
      this.initTableEvents();
      this.initAutoSave();
      this.initUnsavedChangesWarning();
      this.initFormValidation();
      this.initImportModal();
      this.initGenerateDatesButtons();
      this.initSaveButtons();
      this.initDataManagementButtons();
      this.initHijriRecalculate();
      this.initYearArchiveBrowser();
      this.initPdfUpload();
      // default to first tab if present
      const firstTab = $('.mosque-month-tab').first();
      if (firstTab.length) {
        this.switchToMonth(parseInt(firstTab.data('month'), 10) || 1);
      }
    },

    // ---------------- Tabs (modern only) ----------------
    initMonthTabs() {
      const self = this;
      $(document).off('click.mt-tabs')
        .on('click.mt-tabs', '.mosque-month-tab', function (e) {
          e.preventDefault();
          const monthNumber = parseInt($(this).data('month'), 10) || 1;
          if (self.config.unsavedChanges && !confirm(self.t('unsavedChanges', 'You have unsaved changes. Continue?'))) return;
          self.switchToMonth(monthNumber);
        });
    },

    switchToMonth(monthNumber) {
      $('.mosque-month-tab').removeClass('nav-tab-active active');
      $(`.mosque-month-tab[data-month="${monthNumber}"]`).addClass('nav-tab-active active');
      $('.month-panel').removeClass('active');
      $(`#month-panel-${monthNumber}`).addClass('active');

      this.config.currentMonth = monthNumber;
      this.config.unsavedChanges = false;
      this.hideUnsavedChangesWarning();
      this.loadMonthData(monthNumber);
      this.updateMonthIndicators();
    },

    // ---------------- Data loading/render ----------------
    loadMonthData(monthNumber) {
      const panel = $(`#month-panel-${monthNumber}`);
      let container = panel.find('.mosque-admin-table-wrapper');
      if (!container.length) container = $('<div class="mosque-admin-table-wrapper"></div>').appendTo(panel);
      this.showLoading(container);

      $.ajax({
        url: this.config.ajaxUrl,
        method: 'POST',
        data: { action: 'get_month_timetable', nonce: this.config.nonce, month: monthNumber, year: this.config.currentYear }
      }).done((response) => {
        if (response && response.success) {
          this.renderMonthTable(monthNumber, response.data);
        } else {
          this.showError((response && response.data) || this.t('loadError', 'Failed to load month data'));
        }
      }).fail((xhr, _s, err) => {
        console.error('Load month error:', xhr.responseText || err);
        this.showError(this.t('connectionError', 'Error connecting to server: ') + (err || ''));
      });
    },

    renderMonthTable(monthNumber, data) {
      const panel = $(`#month-panel-${monthNumber}`);
      let container = panel.find('.mosque-admin-table-wrapper');
      if (!container.length) container = $('<div class="mosque-admin-table-wrapper"></div>').appendTo(panel);

      const daysInMonth = this.getDaysInMonth(monthNumber, this.config.currentYear);
      let html = `
        <table class="mosque-admin-table">
          <thead>
            <tr>
              <th>Day</th><th>Date</th><th>Hijri Date</th>
              <th>Fajr Start</th><th>Fajr Jamaat</th>
              <th>Sunrise</th>
              <th>Zuhr Start</th><th>Zuhr Jamaat</th>
              <th>Asr Start</th><th>Asr Jamaat</th>
              <th>Maghrib Start</th><th>Maghrib Jamaat</th>
              <th>Isha Start</th><th>Isha Jamaat</th>
              <th>Jummah 1</th><th>Jummah 2</th>
            </tr>
          </thead>
          <tbody>
      `;

      const rows = (data && data.days) || [];
      for (let day = 1; day <= daysInMonth; day++) {
        const iso = this.formatDateForInput(this.config.currentYear, monthNumber, day);
        const rowData = rows.find(d => parseInt(d.day_number, 10) === day) || {};
        const isFriday = this.isFriday(this.config.currentYear, monthNumber, day);

        html += `<tr class="${isFriday ? 'friday-row' : ''}" data-day="${day}">`;
        html += `<td><span class="day-number">${day}</span></td>`;
        html += `
          <td><input type="date" name="date_full[${day}]" value="${rowData.date_full || iso}" class="date-input" data-day="${day}"></td>
          <td><input type="text" name="hijri_date[${day}]" value="${rowData.hijri_date || ''}" class="hijri-date" readonly></td>
        `;

        const prayers = [
          'fajr_start','fajr_jamaat','sunrise',
          'zuhr_start','zuhr_jamaat','asr_start','asr_jamaat',
          'maghrib_start','maghrib_jamaat','isha_start','isha_jamaat',
          'jummah_1','jummah_2'
        ];

        prayers.forEach((p) => {
          const val = rowData[p] || '';
          const required = !p.startsWith('jummah');
          html += `
            <td>
              <input type="time" name="${p}[${day}]" value="${val}"
                     class="prayer-time-input" data-prayer="${p}" data-day="${day}"
                     ${required ? 'required' : ''}>
            </td>`;
        });

        html += '</tr>';
      }

      html += '</tbody></table>';
      container.html(html);
      this.initTableEvents(); // ensure new inputs are bound
    },

    // ---------------- Table Events / Validation ----------------
    initTableEvents() {
      const self = this;

      $(document).off('change.mta-date').on('change.mta-date', '.date-input', function () {
        const day = $(this).data('day');
        const v = $(this).val();
        if (v) {
          self.calculateHijriDate(day, v);
          self.markAsUnsaved();
        }
      });

      $(document).off('change.mta-time').on('change.mta-time', '.prayer-time-input', function () {
        self.markAsUnsaved();
        self.validatePrayerTime($(this));
      });

      // Row paste helper: paste 12–14 tab-separated times into first time input to fill the row
      $(document).off('paste.mta-row')
        .on('paste.mta-row', '.mosque-admin-table tbody tr .prayer-time-input:first', function (e) {
          const txt = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
          if (!txt) return;
          const parts = txt.trim().split(/\t|,|;/).map(s => s.trim());
          if (parts.length < 12) return;
          const inputs = $(this).closest('tr').find('.prayer-time-input');
          inputs.each(function (i) { if (parts[i]) $(this).val(parts[i]).trigger('change'); });
          e.preventDefault();
        });
    },

    calculateHijriDate(day, gregorianDate) {
      $.post(this.config.ajaxUrl, {
        action: 'calculate_hijri_date',
        nonce: this.config.nonce,
        date: gregorianDate
      }).done((res) => {
        if (res && res.success) {
          $(`.hijri-date[name="hijri_date[${day}]"]`).val(res.data);
        }
      });
    },

    validatePrayerTime($input) {
      const value = $input.val();
      const prayer = $input.data('prayer');

      if (value && !this.isValidTime(value)) {
        $input.addClass('error');
        this.showError(this.t('invalidTime', 'Invalid time format. Please use HH:MM format.'));
        return false;
      } else {
        $input.removeClass('error');
      }

      if (value && prayer) {
        const $row = $input.closest('tr');
        const sunrise = $row.find('[data-prayer="sunrise"]').val();
        const maghribStart = $row.find('[data-prayer="maghrib_start"]').val();

        switch (prayer) {
          case 'fajr_start':
          case 'fajr_jamaat':
            if (sunrise && this.compareTime(value, sunrise) >= 0) {
              $input.addClass('error');
              this.showError('Fajr time must be before Sunrise');
              return false;
            }
            break;
          case 'maghrib_start':
          case 'maghrib_jamaat':
            if (!this.isReasonableTime(value, 'maghrib_start')) {
              $input.addClass('warning');
              this.showWarning('Maghrib time seems unusual for this location');
            }
            break;
          case 'isha_start':
          case 'isha_jamaat':
            if (maghribStart && this.compareTime(value, maghribStart) <= 0) {
              $input.addClass('error');
              this.showError('Isha time must be after Maghrib');
              return false;
            }
            break;
        }
      }

      return this.validatePrayerSequence($input);
    },

    validatePrayerSequence($input) {
      const day = $input.data('day') || $input.closest('tr').find('.day-number').text();
      const $row = $input.closest('tr');

      const times = {};
      $row.find('.prayer-time-input').each(function () {
        const p = $(this).data('prayer');
        const v = $(this).val();
        if (v) times[p] = v;
      });

      const seq = ['fajr_start', 'sunrise', 'zuhr_start', 'asr_start', 'maghrib_start', 'isha_start'];
      let bad = false;

      for (let i = 1; i < seq.length; i++) {
        const prev = times[seq[i - 1]];
        const curr = times[seq[i]];
        const $curr = $row.find(`input[data-prayer="${seq[i]}"]`);
        if (prev && curr && this.compareTime(prev, curr) >= 0) {
          $curr.addClass('sequence-error');
          this.showError(`Day ${day}: ${seq[i - 1]} must be before ${seq[i]}`);
          bad = true;
        } else {
          $curr.removeClass('sequence-error');
        }
      }

      const jamaatPairs = [
        ['fajr_start', 'fajr_jamaat'],
        ['zuhr_start', 'zuhr_jamaat'],
        ['asr_start', 'asr_jamaat'],
        ['maghrib_start', 'maghrib_jamaat'],
        ['isha_start', 'isha_jamaat']
      ];
      jamaatPairs.forEach(([start, jamaat]) => {
        const s = times[start], j = times[jamaat];
        const $j = $row.find(`input[data-prayer="${jamaat}"]`);
        if (s && j && this.compareTime(j, s) < 0) {
          $j.addClass('sequence-error');
          this.showError(`Day ${day}: ${jamaat} cannot be before ${start}`);
          bad = true;
        } else {
          $j.removeClass('sequence-error');
        }
      });

      return !bad;
    },

    // ---------------- Save / Auto-save ----------------
    initAutoSave() {
      $(document).off('click.mta-save-now')
        .on('click.mta-save-now', '.mosque-save-now-btn', () => this.saveMonth(this.config.currentMonth));
    },

    triggerAutoSave() {
      if (this.config.autoSaveTimer) clearTimeout(this.config.autoSaveTimer);
      this.config.autoSaveTimer = setTimeout(() => {
        if (this.config.unsavedChanges) this.saveMonth(this.config.currentMonth, true);
      }, 30000);
    },

    saveMonth(monthNumber, isAutoSave = false) {
      const panel = $(`#month-panel-${monthNumber}`);
      const payload = this.collectMonthData(panel);
      if (!isAutoSave) $('.save-month-btn').addClass('mosque-btn-loading');

      $.ajax({
        url: this.config.ajaxUrl,
        method: 'POST',
        data: { action: 'save_month_timetable', nonce: this.config.nonce, month: monthNumber, year: this.config.currentYear, data: payload }
      }).done((res) => {
        $('.save-month-btn').removeClass('mosque-btn-loading');
        if (res && res.success) {
          if (!isAutoSave) this.showSuccess(this.t('saveSuccess', 'Saved successfully.'));
          this.config.unsavedChanges = false;
          this.hideUnsavedChangesWarning();
          this.updateMonthIndicators();
        } else {
          this.showError((res && res.data) || this.t('saveError', 'Failed to save.'));
        }
      }).fail((xhr, _s, err) => {
        $('.save-month-btn').removeClass('mosque-btn-loading');
        if (xhr?.responseJSON?.data?.includes('Security check failed')) {
          this.showError('Security check failed. Please refresh.');
          this.refreshNonce();
        } else {
          this.showError('Error saving data: ' + (err || ''));
        }
      });
    },

    collectMonthData($panel) {
      const data = { days: [] };
      $panel.find('.mosque-admin-table tbody tr').each(function () {
        const $row = $(this), day = $row.data('day');
        const dayData = { day_number: day };
        $row.find('input').each(function () {
          const name = $(this).attr('name'), val = $(this).val();
          if (!name) return;
          const field = name.replace(/\[\d+\]$/, '');
          dayData[field] = val || '';
        });
        data.days.push(dayData);
      });
      return data;
    },

    initUnsavedChangesWarning() {
      $(window).off('beforeunload.mta-unsaved')
        .on('beforeunload.mta-unsaved', (e) => {
          if (!this.config.unsavedChanges) return;
          const msg = this.t('unsavedChanges', 'You have unsaved changes.');
          e.originalEvent.returnValue = msg;
          return msg;
        });
    },

    markAsUnsaved() {
      this.config.unsavedChanges = true;
      this.showUnsavedChangesWarning();
    },

    showUnsavedChangesWarning() {
      if (!$('.mosque-unsaved-changes').length) {
        $('body').append(`
          <div class="mosque-unsaved-changes">
            <div class="mosque-unsaved-text">${this.t('unsavedChanges', 'You have unsaved changes')}</div>
            <button class="mosque-save-now-btn">${this.t('saveNow', 'Save Now')}</button>
          </div>
        `);
      }
      $('.mosque-unsaved-changes').addClass('show');
    },

    hideUnsavedChangesWarning() {
      $('.mosque-unsaved-changes').removeClass('show');
    },

    initFormValidation() {
      $(document).off('submit.mta-form').on('submit.mta-form', '.mosque-admin-form', (e) => {
        if (!this.validateForm($(e.currentTarget))) e.preventDefault();
      });
    },

    validateForm($form) {
      let ok = true;
      $form.find('input[required]').each(function () {
        if (!$(this).val()) { $(this).addClass('error'); ok = false; } else { $(this).removeClass('error'); }
      });
      return ok;
    },

    updateMonthIndicators() {
      // Hook for future "has-data" badges, etc.
    },

    // ---------------- Import Modal ----------------
    initImportModal() {
      const ajaxurl = this.config.ajaxUrl, nonce = this.config.nonce;

      $(document).off('click.mt-import-open')
        .on('click.mt-import-open', '#csv-import-btn,#xlsx-import-btn,#paste-import-btn', function () {
          const method = this.id.includes('csv') ? 'csv' : (this.id.includes('xlsx') ? 'xlsx' : 'paste');
          $('#import-modal').addClass('show').fadeIn();
          $('.import-tab-btn').removeClass('active');
          $(`.import-tab-btn[data-method="${method}"]`).addClass('active');
          $('.import-method').removeClass('active');
          $(`#${method}-import`).addClass('active');
        });

      $(document).off('click.mt-import-close')
        .on('click.mt-import-close', '.mosque-modal-close,#cancel-import', () => $('#import-modal').removeClass('show').fadeOut());

      $(document).off('click.mt-import-tab')
        .on('click.mt-import-tab', '.import-tab-btn', function () {
          const method = $(this).data('method');
          $('.import-tab-btn').removeClass('active'); $(this).addClass('active');
          $('.import-method').removeClass('active'); $('#' + method + '-import').addClass('active');
        });

      const finish = (ok, msg) => {
        if (ok) {
          $('#import-modal').removeClass('show').fadeOut();
          this.showSuccess(msg || this.t('importSuccess', 'Import completed successfully!'));
          this.loadMonthData(this.config.currentMonth);
          this.updateMonthIndicators();
        } else {
          this.showError(msg || this.t('importError', 'Error importing file.'));
        }
      };

      $(document).off('click.mt-import-go')
        .on('click.mt-import-go', '#execute-import', () => {
          const month = $('#import-month').val();
          const method = $('.import-tab-btn.active').data('method') || 'csv';
          if (!month) return finish(false, this.t('noMonth', 'Please select a month.'));

          if (method === 'csv') {
            const f = document.getElementById('csv-file');
            if (!f?.files?.[0]) return finish(false, this.t('noFile', 'Please select a file.'));
            const fd = new FormData();
            fd.append('action', 'import_csv_timetable'); fd.append('nonce', nonce);
            fd.append('month', month); fd.append('csv_file', f.files[0]);
            $.ajax({ url: ajaxurl, method: 'POST', data: fd, processData: false, contentType: false })
              .done(resp => finish(resp?.success, resp?.data || this.t('importSuccess', 'CSV imported.')))
              .fail(xhr => finish(false, this.t('importError', 'CSV import error: ') + (xhr.statusText || xhr.status)));
          } else if (method === 'xlsx') {
            const xf = document.getElementById('xlsx-file');
            if (!xf?.files?.[0]) return finish(false, this.t('noFile', 'Please select a file.'));
            const xfd = new FormData();
            xfd.append('action', 'import_xlsx_timetable'); xfd.append('nonce', nonce);
            xfd.append('month', month); xfd.append('xlsx_file', xf.files[0]);
            $.ajax({ url: ajaxurl, method: 'POST', data: xfd, processData: false, contentType: false })
              .done(resp => finish(resp?.success, resp?.data || this.t('importSuccess', 'Excel imported.')))
              .fail(xhr => finish(false, this.t('importError', 'XLSX import error: ') + (xhr.statusText || xhr.status)));
          } else {
            const text = ($('#paste-data').val() || '').trim();
            if (!text) return finish(false, this.t('noPaste', 'Please paste data.'));
            $.post(ajaxurl, { action: 'import_paste_data', nonce, month, paste_data: text })
              .done(resp => finish(resp?.success, resp?.data || this.t('importSuccess', 'Pasted data imported.')))
              .fail(xhr => finish(false, this.t('importError', 'Paste import error: ') + (xhr.statusText || xhr.status)));
          }
        });
    },

    // ---------------- Generate / Save controls ----------------
    initGenerateDatesButtons() {
      $(document).off('click.mt-gen-all')
        .on('click.mt-gen-all', '#generate-all-dates', () => {
          if (!confirm(this.t('confirmGenerateAll', 'Generate date structure for all 12 months?'))) return;
          this.generateAllDates();
        });

      $(document).off('click.mt-gen-month')
        .on('click.mt-gen-month', '.generate-month-dates', (e) => {
          const month = parseInt($(e.currentTarget).data('month'), 10) || this.config.currentMonth;
          this.generateMonthDates(month);
        });
    },

    initSaveButtons() {
      $(document).off('click.mt-save-all')
        .on('click.mt-save-all', '#save-all-months', () => {
          if (!confirm(this.t('confirmSaveAll', 'Save all months?'))) return;
          this.saveAllMonths();
        });

      $(document).off('click.mt-save-month')
        .on('click.mt-save-month', '.save-month-btn', (e) => {
          const month = parseInt($(e.currentTarget).data('month'), 10) || this.config.currentMonth;
          this.saveMonth(month);
        });
    },

    initDataManagementButtons() {
      $(document).off('click.mt-export-csv')
        .on('click.mt-export-csv', '#export-csv-btn', () => {
          window.location.href = `${this.config.ajaxUrl}?action=export_csv_calendar&nonce=${this.config.nonce}`;
          this.showSuccess(this.t('exportingCsv', 'CSV export started…'));
        });

      // Optional: attach your clear/reset/regenerate handlers if present in UI
    },

    generateAllDates() {
      $('#generate-all-dates').addClass('mosque-btn-loading');
      $.post(this.config.ajaxUrl, { action: 'generate_all_dates', nonce: this.config.nonce, year: this.config.currentYear })
        .done((res) => {
          $('#generate-all-dates').removeClass('mosque-btn-loading');
          if (res?.success) {
            this.showSuccess(this.t('generateSuccess', 'All dates generated successfully!'));
            this.loadMonthData(this.config.currentMonth);
            this.updateMonthIndicators();
          } else {
            this.showError(res?.data || this.t('generateError', 'Failed to generate dates'));
          }
        }).fail((_x, _s, err) => {
          $('#generate-all-dates').removeClass('mosque-btn-loading');
          this.showError(this.t('connectionError', 'Error connecting to server: ') + (err || ''));
        });
    },

    generateMonthDates(month) {
      const $btn = $(`.generate-month-dates[data-month="${month}"]`);
      $btn.addClass('mosque-btn-loading');
      $.post(this.config.ajaxUrl, { action: 'generate_month_dates', nonce: this.config.nonce, month, year: this.config.currentYear })
        .done((res) => {
          if (res?.success) {
            this.showSuccess(this.t('generateMonthSuccess', 'Month dates generated.'));
            if (month === this.config.currentMonth) this.loadMonthData(month);
          } else {
            this.showError(res?.data || this.t('generateError', 'Failed to generate dates'));
          }
        }).fail((_x, _s, err) => {
          this.showError(this.t('connectionError', 'Error connecting to server: ') + (err || ''));
        }).always(() => $btn.removeClass('mosque-btn-loading'));
    },

    saveAllMonths() {
      $('#save-all-months').addClass('mosque-btn-loading');
      const all = {};
      for (let m = 1; m <= 12; m++) {
        const panel = $(`#month-panel-${m}`);
        if (panel.length) all[m] = this.collectMonthData(panel);
      }
      $.ajax({
        url: this.config.ajaxUrl, method: 'POST',
        data: { action: 'save_all_months', nonce: this.config.nonce, year: this.config.currentYear, data: all }
      }).done((res) => {
        $('#save-all-months').removeClass('mosque-btn-loading');
        if (res?.success) {
          this.showSuccess(this.t('saveSuccess', 'All months saved successfully!'));
          this.config.unsavedChanges = false;
          this.hideUnsavedChangesWarning();
          this.updateMonthIndicators();
        } else {
          this.showError(res?.data || this.t('saveError', 'Failed to save all months'));
        }
      }).fail(() => {
        $('#save-all-months').removeClass('mosque-btn-loading');
        this.showError(this.t('saveError', 'Error saving data'));
      });
    },

    // ---------------- Hijri Recalc ----------------
    initHijriRecalculate() {
      $(document).off('click.mt-hijri')
        .on('click.mt-hijri', '.recalc-hijri-btn', (e) => {
          const $btn = $(e.currentTarget);
          const month = parseInt($btn.data('month'), 10) || this.config.currentMonth;
          const adj = parseInt($(`#hijri-adj-${month}`).val(), 10) || 0;
          if (!confirm(this.t('confirmHijri', `Recalculate Hijri dates with ${adj} day adjustment?`))) return;
          this.recalculateHijriDates(month, adj, $btn);
        });
    },

    recalculateHijriDates(month, adjustment, $button) {
      $button.addClass('mosque-btn-loading');
      $.post(this.config.ajaxUrl, {
        action: 'recalculate_hijri_dates', nonce: this.config.nonce, month, adjustment, year: this.config.currentYear
      }).done((res) => {
        $button.removeClass('mosque-btn-loading');
        if (res?.success) {
          this.showSuccess(this.t('hijriRecalc', `Hijri dates recalculated (adj ${adjustment}).`));
          if (month === this.config.currentMonth) this.loadMonthData(month);
        } else {
          this.showError(res?.data || this.t('hijriError', 'Failed to recalculate Hijri dates'));
        }
      }).fail((_x, _s, err) => {
        $button.removeClass('mosque-btn-loading');
        this.showError(this.t('connectionError', 'Error connecting to server: ') + (err || ''));
      });
    },

    // ---------------- Year / Header ----------------
    initYearArchiveBrowser() {
      $(document).off('click.mt-year-load')
        .on('click.mt-year-load', '#load-year-data', () => {
          const yr = parseInt($('#year-selector').val(), 10) || this.config.currentYear;
          if (yr) this.loadYearData(yr);
        });

      $(document).off('click.mt-year-new')
        .on('click.mt-year-new', '#create-new-year', () => {
          const yr = parseInt($('#year-selector').val(), 10) || this.config.currentYear;
          if (yr && confirm(this.t('confirmCreateYear', `Create data structure for year ${yr}?`))) this.createNewYear(yr);
        });

      $(document).off('change.mt-year-select')
        .on('change.mt-year-select', '#year-selector', () => {
          const yr = parseInt($('#year-selector').val(), 10) || this.config.currentYear;
          this.updateYearInfo(yr);
        });
    },

    loadYearData(year) {
      this.config.currentYear = year;
      this.showSuccess(this.t('loadingYear', `Loading data for year ${year}…`));
      this.loadMonthData(this.config.currentMonth);
      $('.mosque-page-header h1').text(`Prayer Timetables - ${year}`);
    },

    createNewYear(year) {
      $('#create-new-year').addClass('mosque-btn-loading');
      $.post(this.config.ajaxUrl, { action: 'generate_all_dates', nonce: this.config.nonce, year })
        .done((res) => {
          $('#create-new-year').removeClass('mosque-btn-loading');
          if (res?.success) {
            this.showSuccess(this.t('yearCreated', `Year ${year} created.`));
            this.loadYearData(year);
          } else {
            this.showError(res?.data || this.t('yearCreateError', 'Failed to create year.'));
          }
        }).fail((_x, _s, err) => {
          $('#create-new-year').removeClass('mosque-btn-loading');
          this.showError(this.t('connectionError', 'Error connecting to server: ') + (err || ''));
        });
    },

    updateYearInfo(year) {
      this.config.currentYear = parseInt(year, 10) || this.config.currentYear;
    },

    // === PDF Upload + Removal (initPdfUpload) ===
    initPdfUpload: function() {
      const self = this;

      // File input change → show filename + reveal Upload/Replace
      $(document).on('change', '.mt-pdf-file-input', function() {
        const $input = $(this);
        const $label = $input.siblings('label');
        const $btn   = $input.siblings('.mt-upload-pdf-btn');

        if ($input[0].files && $input[0].files[0]) {
          $label.text('📁 ' + $input[0].files[0].name);
          $btn.text($btn.text().match(/Replace/i) ? '📤 Replace' : '📤 Upload').show();
        } else {
          $label.text('📁 Choose PDF File');
          $btn.hide();
        }
      });

      // Upload / Replace
      $(document).on('click', '.mt-upload-pdf-btn', function() {
        const $btn   = $(this);
        const $form  = $btn.closest('.mt-pdf-upload-form');
        const month  = $btn.data('month');
        const fileEl = $form.find('.mt-pdf-file-input')[0];

        if (!fileEl || !fileEl.files || !fileEl.files[0]) {
          self.showError(self.t('pdfSelectFirst', 'Please select a PDF file first'));
          return;
        }
        const file = fileEl.files[0];
        if (file.type !== 'application/pdf') {
          self.showError(self.t('pdfInvalidFile', 'Please select a valid PDF file'));
          return;
        }

        $btn.addClass('mosque-btn-loading').prop('disabled', true);

        const fd = new FormData();
        fd.append('action', 'upload_month_pdf');
        fd.append('nonce',  self.config.nonce);
        fd.append('month',  month);
        fd.append('year',   self.config.currentYear);
        fd.append('pdf_file', file);

        $.ajax({
          url: self.config.ajaxUrl,
          method: 'POST',
          data: fd,
          processData: false,
          contentType: false
        }).done(function(res){
          $btn.removeClass('mosque-btn-loading').prop('disabled', false);

          if (res && res.success && res.data && res.data.url) {
            self.showSuccess(self.t('pdfUploadSuccess', 'PDF uploaded successfully!'));

            // Update UI immediately (no reload required)
            const $section = $btn.closest('.mt-pdf-upload-section');
            const $form    = $btn.closest('.mt-pdf-upload-form');
            let $current   = $section.find('.mt-pdf-current');

            if ($current.length === 0) {
              $current = $(`
                <div class="mt-pdf-current">
                  <span class="mt-pdf-info">✅ PDF uploaded</span>
                  <a href="${res.data.url}" target="_blank" class="button button-secondary">📖 View PDF</a>
                  <button type="button" class="button button-link-delete mt-remove-pdf-btn" data-month="${month}" data-year="${self.config.currentYear}">Remove</button>
                </div>
              `);
              $current.insertBefore($form);
            } else {
              $current.find('a.button.button-secondary').attr('href', res.data.url).show();
              $current.find('.mt-remove-pdf-btn').show();
            }

            // Reset chooser
            fileEl.value = '';
            $form.find('label').text('📁 Choose PDF File');
            $btn.text('📤 Replace').hide(); // hidden until another file is chosen

          } else {
            self.showError((res && res.data) || self.t('pdfUploadError', 'PDF upload failed'));
          }
        }).fail(function(_x, _s, err){
          $btn.removeClass('mosque-btn-loading').prop('disabled', false);
          self.showError(self.t('pdfUploadError', 'PDF upload failed') + ': ' + (err || ''));
        });
      });

      // Remove
      $(document).on('click', '.mt-remove-pdf-btn', function() {
        const $btn   = $(this);
        const month  = $btn.data('month');

        if (!confirm(self.t('confirmRemovePdf', 'Are you sure you want to remove this PDF?'))) return;

        $btn.addClass('mosque-btn-loading').prop('disabled', true);

        $.post(self.config.ajaxUrl, {
          action: 'remove_month_pdf',
          nonce:  self.config.nonce,
          month:  month,
          year:   self.config.currentYear
        }).done(function(res){
          $btn.removeClass('mosque-btn-loading').prop('disabled', false);

          if (res && res.success) {
            self.showSuccess(self.t('pdfRemoveSuccess', 'PDF removed successfully!'));

            const $section = $btn.closest('.mt-pdf-upload-section');
            const $form    = $section.find('.mt-pdf-upload-form');
            const $label   = $form.find('label');
            const $upload  = $form.find('.mt-upload-pdf-btn');

            // Remove "current" block
            $btn.closest('.mt-pdf-current').remove();

            // Reset chooser
            $form.find('.mt-pdf-file-input').val('');
            $label.text('📁 Choose PDF File');
            $upload.text('📤 Upload').hide(); // hidden until a new file is chosen

          } else {
            self.showError((res && res.data) || self.t('pdfRemoveError', 'PDF removal failed'));
          }
        }).fail(function(_x,_s,err){
          $btn.removeClass('mosque-btn-loading').prop('disabled', false);
          self.showError(self.t('pdfRemoveError', 'PDF removal failed') + ': ' + (err || ''));
        });
      });
    },

    // ---------------- Utils ----------------
    compareTime(a, b) {
      const [h1, m1] = a.split(':').map(Number);
      const [h2, m2] = b.split(':').map(Number);
      return (h1 * 60 + m1) - (h2 * 60 + m2);
    },

    isValidTime(timeString) {
      return /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(timeString);
    },

    isReasonableTime(time, prayerKey) {
      const [hour] = time.split(':').map(Number);
      switch (prayerKey) {
        case 'fajr_start': return hour >= 2 && hour <= 9;
        case 'maghrib_start': return hour >= 15 && hour <= 22;
        case 'isha_start': return hour >= 18 && hour <= 23;
        default: return true;
      }
    },

    getDaysInMonth(month, year) { return new Date(year, month, 0).getDate(); },
    isFriday(year, month, day) { return new Date(year, month - 1, day).getDay() === 5; },
    formatDateForInput(year, month, day) {
      return `${year}-${String(month).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
    },

    refreshNonce() {
      $.post(this.config.ajaxUrl, { action: 'refresh_admin_nonce' })
        .done((res) => { if (res?.success && res.data?.nonce) this.config.nonce = res.data.nonce; });
    },

    showLoading($container) {
      $container.html('<div class="mosque-loading"><div class="mosque-spinner"></div>Loading...</div>');
    },
    showError(msg) { this.showMessage(msg, 'error'); },
    showSuccess(msg) { this.showMessage(msg, 'success'); },
    showWarning(msg) { this.showMessage(msg, 'warning'); },
    showMessage(message, type) {
      const html = `
        <div class="mosque-message mosque-message-${type}">
          <div class="mosque-message-icon">${type === 'error' ? '⚠️' : type === 'warning' ? '⚠️' : '✅'}</div>
          <div>${message}</div>
          <button class="mosque-message-close">&times;</button>
        </div>`;
      $('.mosque-message').remove();
      if ($('.wrap').length) $('.wrap').prepend(html); else $('body').prepend(html);
      setTimeout(() => $('.mosque-message').fadeOut(), 5000);
      $(document).off('click.mt-msg-close').on('click.mt-msg-close', '.mosque-message-close', function () {
        $(this).closest('.mosque-message').fadeOut();
      });
    }
  };

  $(document).ready(function () {
    MosqueTimetableAdmin.init();
  });

})(jQuery);
