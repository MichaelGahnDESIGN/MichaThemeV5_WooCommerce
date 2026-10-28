/* MichaThemeV5 - Woo: Glocke nur sichtbar wenn echte Einträge, Herzen nach Ajax abgleichen. Keine Drittanbieter. */
(function () {
  'use strict';

  // Glocke aus MGD Notification Center - Plugin zeigt sie auch bei 0 Einträgen, deshalb Wrapper hidden
  function watchBell(wrap) {
    var list = wrap.querySelector('.mgd-notification-center__list');
    if (!list) { return; }
    function check() { wrap.hidden = list.children.length === 0; }
    check();
    if (typeof MutationObserver !== 'undefined') {
      new MutationObserver(check).observe(list, { childList: true });
    }
  }
  function init() {
    document.querySelectorAll('[data-mt-notify-item]').forEach(watchBell);
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }

  // Woo lädt Karten per Ajax nach (jQuery-Event)
  if (window.jQuery) {
    window.jQuery(document.body).on('updated_wc_div wc_fragments_refreshed', function () {
      document.dispatchEvent(new Event('mt:favs-sync'));
    });
  }
})();
