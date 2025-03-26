(function localgovAlertBannerCollapsibleScript(Drupal) {

  Drupal.behaviors.localgovAlertBannersCollapsible = {
    attach(context) {
      const alertBannerButtons = once(
        'alertBannerButtons',
        '.js-alert-banner-pane-button',
        context,
      );

      if (alertBannerButtons) {
        alertBannerButtons.forEach(function (button) {
          
          // Get related elements.
          const pane = button.closest('.js-alert-banner-pane');
          const contents = pane.querySelectorAll('.js-alert-banner-pane-content')[0];
          
          // Get current state.
          let state = window.localStorage.getItem('localgovAlertBannerCollapsibleState');
          
          // Hide if default closed.
          if (state == 0) {
            contents.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
            button.textContent = button.dataset.closedLabel;
          }

          // Remove the hide buttons is JS
          // @todo move to PHP or template.
          const hideButtons = contents.querySelectorAll('.js-localgov-alert-banner__close');
          hideButtons.forEach(function (hideButton) {
            hideButton.remove();
          });

          // Click handler for the show / hide button.
          button.addEventListener('click', function (e) {
            e.preventDefault();

            // Toggle the state.
            state = state == 1 ? 0 : 1;

            // Toggle the content pane to show / hide.
            contents.classList.toggle('hidden');

            // Toggle the button aria-expanded attribute.
            this.setAttribute('aria-expanded', state == 0 ? 'false' : 'true');

            // Set the label for the show / hide button.
            this.textContent = state == 0 ? this.dataset.closedLabel: this.dataset.openLabel;

            // Set the state back to local storage.
            window.localStorage.setItem('localgovAlertBannerCollapsibleState', state);

          });
        });
      }
    }
  }
})(Drupal);