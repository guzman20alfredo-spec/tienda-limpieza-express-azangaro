"use strict";

;
(function ($, window, document, undefined) {
  jQuery(document).ready(function ($) {
    if (typeof wp.codeEditor !== 'undefined') {
      var settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};

      // To use a theme need to get required CSS from https://github.com/codemirror/codemirror5/tree/master/theme
      // settings.codemirror.theme = '3024-day';

      // Initialize codeEditor for CSS fields
      $('.merchant-field-custom_css textarea').each(function () {
        var editor = wp.codeEditor.initialize(this, settings).codemirror;

        // Trigger event on any change
        editor.on('change', function () {
          $(editor.getTextArea()).trigger('change');
        });
      });

      // Initialize codeEditor for JS fields
      $('.merchant-field-custom_js textarea').each(function () {
        var jsSettings = _.clone(settings);
        jsSettings.codemirror.mode = 'javascript';
        var editor = wp.codeEditor.initialize(this, jsSettings).codemirror;

        // Trigger event on any change
        editor.on('change', function () {
          $(editor.getTextArea()).trigger('change');
        });
      });
    }

    // White Label upsell modal.
    var $whiteLabelModal = $('#merchant-white-label-modal');
    if ($whiteLabelModal.length) {
      var openWhiteLabelModal = function openWhiteLabelModal(e) {
        e.preventDefault();
        $whiteLabelModal.addClass('merchant-show').attr('aria-hidden', 'false');
      };
      var closeWhiteLabelModal = function closeWhiteLabelModal() {
        $whiteLabelModal.removeClass('merchant-show').attr('aria-hidden', 'true');
      };
      $(document).on('click', '.merchant-white-label-field', function (e) {
        if ($(e.target).closest('a, .merchant-white-label-recheck').length) {
          return;
        }
        openWhiteLabelModal(e);
      });
      $whiteLabelModal.on('click', '.merchant-white-label-modal-close', closeWhiteLabelModal);
      $whiteLabelModal.on('click', function (e) {
        if (e.target === this) {
          closeWhiteLabelModal();
        }
      });
      $(document).on('keyup', function (e) {
        if ('Escape' === e.key) {
          closeWhiteLabelModal();
        }
      });
    }
  });
})(jQuery, window, document);