/* Unified bindings for Timetables admin */
jQuery(function($){
  if (typeof MT_ADMIN === 'undefined') return;

  function post(action, payload){
    return $.post(MT_ADMIN.ajax, Object.assign({_ajax_nonce: MT_ADMIN.nonce, action}, payload||{}));
  }
  function loadMonth(postId, year, month){
    return post('mt_get_month',{post_id:postId,year,month}).done(function(res){
      if(!res || !res.success) return;
      const panel = $('#month-panel-'+month);
      let wrap = panel.find('.mosque-admin-table-wrapper');
      if(!wrap.length) wrap = $('<div class="mosque-admin-table-wrapper"/>').appendTo(panel);
      wrap.html(res.data.html||'');
    });
  }

  // Month tab click (expects .mosque-month-tab + #month-panel-n in your page)
  $(document).on('click','.mosque-month-tab',function(e){
    e.preventDefault();
    const m=$(this).data('month');
    MT_ADMIN.currentMonth=m;
    $('.mosque-month-tab').removeClass('active'); $(this).addClass('active');
    $('.month-panel').removeClass('active'); $('#month-panel-'+m).addClass('active');
    loadMonth(MT_ADMIN.postId, MT_ADMIN.currentYear, m);
  });

  // Year controls
  $(document).on('change','#year-selector',function(){
    MT_ADMIN.currentYear = parseInt($(this).val(),10);
    loadMonth(MT_ADMIN.postId, MT_ADMIN.currentYear, MT_ADMIN.currentMonth);
  });
  $(document).on('click','#load-year',function(e){
    e.preventDefault();
    loadMonth(MT_ADMIN.postId, MT_ADMIN.currentYear, MT_ADMIN.currentMonth);
  });
  $(document).on('click','#new-year-btn,#generate-all-dates',function(e){
    e.preventDefault();
    post('mt_generate_year',{post_id:MT_ADMIN.postId,year:MT_ADMIN.currentYear})
      .done(function(res){ if(res?.success){ loadMonth(MT_ADMIN.postId, MT_ADMIN.currentYear, MT_ADMIN.currentMonth); alert('Year generated.'); }});
  });

  // Month actions
  $(document).on('click','#generate-month',function(e){
    e.preventDefault();
    const $b=$(this);
    post('mt_generate_month',{post_id:$b.data('post-id'),year:$b.data('year'),month:$b.data('month')})
      .done(function(res){ if(res?.success){ loadMonth($b.data('post-id'),$b.data('year'),$b.data('month')); alert('Dates generated.'); }});
  });
  $(document).on('click','#save-month',function(e){
    e.preventDefault();
    const $b=$(this);
    post('mt_save_month',{post_id:$b.data('post-id'),year:$b.data('year'),month:$b.data('month')})
      .done(function(res){ alert(res?.success?'Saved.':'Save failed.'); });
  });
  $(document).on('click','#recalc-hijri',function(e){
    e.preventDefault();
    const $b=$(this);
    post('mt_recalc_hijri',{post_id:$b.data('post-id'),year:$b.data('year'),month:$b.data('month'),mode:$('#hijri-adjust').val()||'calculated'})
      .done(function(res){ if(res?.success){ loadMonth($b.data('post-id'),$b.data('year'),$b.data('month')); }});
  });
  $(document).on('click','#upload-pdf',function(e){
    e.preventDefault();
    const file=$('#pdf-file')[0]?.files?.[0]; if(!file){ alert('Choose a PDF first.'); return; }
    const $b=$(this), form=new FormData();
    form.append('action','mt_upload_month_pdf'); form.append('_ajax_nonce',MT_ADMIN.nonce);
    form.append('post_id',$b.data('post-id')); form.append('year',$b.data('year')); form.append('month',$b.data('month'));
    form.append('file',file);
    $.ajax({url:MT_ADMIN.ajax,method:'POST',data:form,processData:false,contentType:false})
      .done(function(res){ alert(res?.success?'PDF uploaded.':'Upload failed.'); });
  });

  // Initial fetch
  loadMonth(MT_ADMIN.postId, MT_ADMIN.currentYear, MT_ADMIN.currentMonth);
});
