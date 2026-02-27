// eslint.config.mjs
import js from "@eslint/js";
import globals from "globals";

export default [
  // 0) Ignore heavy/irrelevant paths (flat config ignores replace .eslintignore)
  {
    ignores: [
      ".git/**",
      ".claude/**",
      ".vscode/**",
      "node_modules/**",
      "vendor/**",
      "public_html/wp-admin/**",
      "public_html/wp-includes/**",
      "public_html/core/**",
      "public_html/.private/**",
      "public_html/test-sync/**",
      "public_html/wp-content/plugins/!(mosque-timetable)/**",
      "public_html/wp-content/themes/**",
      "public_html/wp-content/uploads/**",
      "**/*.min.js",
      "**/dist/**",
      "**/build/**",
      "**/*.json",
      "**/*.md"
    ]
  },

  // 1) Base JS recommendations
  js.configs.recommended,

  // 2) Project rules (target ONLY your real JS sources)
  {
    files: [
      "public_html/wp-content/plugins/**/assets/**/*.{js,mjs,cjs}",
      "public_html/wp-content/themes/**/*.{js,mjs,cjs}",
      // If you keep a service worker in assets or root:
      "public_html/**/*sw*.js"
    ],
    languageOptions: {
      sourceType: "script", // classic WP scripts
      globals: {
        ...globals.browser,
        ...globals.jquery,

        // WordPress/browser globals you actually use
        ajaxurl: "readonly",
        wp: "readonly",
        jQuery: "readonly",

        // Your plugin objects
        MosqueTimetable: "readonly",
        MosqueTimetableAdmin: "readonly",
        mosqueTimetable: "readonly",
        mosqueTimetableAdmin: "readonly",
        mosqueTimetableModal: "readonly"
      }
    },
    rules: {
      // Practical defaults for WP/jQuery land
      "no-unused-vars": ["warn", { argsIgnorePattern: "^_", varsIgnorePattern: "^_" }],
      "no-undef": "error",
      "no-console": "off"
    }
  },

  // 3) Service Worker specific override (if you lint it)
  {
    files: ["public_html/**/*sw*.js", "public_html/**/sw.js"],
    languageOptions: {
      globals: {
        // serviceworker environment already provides: self, caches
        // But clients is needed
        clients: "readonly"
      }
    }
  }
];