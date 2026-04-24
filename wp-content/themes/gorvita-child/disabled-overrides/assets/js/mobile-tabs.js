(function () {
  'use strict';

  var BREAKPOINT = 768;

  function isMobile() {
    return window.innerWidth <= BREAKPOINT;
  }

  function initMobileTabs() {
    var tabContainer = document.querySelector('.woocommerce-tabs');
    if (!tabContainer || !isMobile()) return;
    if (tabContainer.dataset.mobileAccordion) return;
    tabContainer.dataset.mobileAccordion = '1';

    var tabList = tabContainer.querySelector('ul.tabs');
    var panels  = tabContainer.querySelectorAll('.woocommerce-tabs .panel');
    if (!tabList || !panels.length) return;

    tabList.style.display = 'none';

    panels.forEach(function (panel) {
      var panelId = panel.id;
      var key = panelId.replace('tab-', '');
      var matchingTab = tabList.querySelector('li.' + key + ' a');
      var labelText = matchingTab ? matchingTab.textContent.trim() : key;

      var trigger = document.createElement('button');
      trigger.className = 'gorvita-tab-trigger';
      trigger.setAttribute('type', 'button');
      trigger.setAttribute('aria-expanded', 'false');
      trigger.setAttribute('aria-controls', panelId);
      trigger.innerHTML =
        '<span>' + labelText + '</span>' +
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<polyline points="6 9 12 15 18 9"></polyline></svg>';

      panel.parentNode.insertBefore(trigger, panel);
      panel.hidden = true;

      trigger.addEventListener('click', function () {
        var expanded = trigger.getAttribute('aria-expanded') === 'true';
        trigger.setAttribute('aria-expanded', String(!expanded));
        trigger.classList.toggle('is-open', !expanded);
        panel.hidden = expanded;
      });
    });

    /* Open first panel by default */
    var firstTrigger = tabContainer.querySelector('.gorvita-tab-trigger');
    var firstPanel   = tabContainer.querySelector('.woocommerce-tabs .panel');
    if (firstTrigger && firstPanel) {
      firstTrigger.setAttribute('aria-expanded', 'true');
      firstTrigger.classList.add('is-open');
      firstPanel.hidden = false;
    }
  }

  document.addEventListener('DOMContentLoaded', initMobileTabs);
})();
