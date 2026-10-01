(function ($) {
  'use strict';
  $(function () {
    $('form.variations_form').each(function () {
      const form = $(this);
      const select = form.find('select[name="attribute_pa_size"]');
      if (!select.length || form.find('.size-options').length) return;
      const variations = form.data('product_variations');
      // Leave WooCommerce's native selector available if variation data is absent.
      if (!Array.isArray(variations)) return;
      const group = $('<div class="size-options" role="group" aria-label="Choose a size"></div>');
      const buttons = [];
      const order=['s','m','l','xl'];
      const selectedSize=select.val();
      select.find('option').sort((a,b)=>order.indexOf(a.value.toLowerCase())-order.indexOf(b.value.toLowerCase())).appendTo(select);
      select.val(selectedSize);
      select.find('option').each(function () {
        if (!this.value) return;
        const value = this.value;
        const matches = variations.filter(v => !v.attributes.attribute_pa_size || v.attributes.attribute_pa_size === value);
        const available = matches.some(v => v.is_in_stock && v.is_purchasable && v.variation_is_active);
        const button = $('<button type="button" class="size-option" aria-pressed="false"></button>')
          .text(this.text + (available ? '' : ' · Sold out'))
          .attr('aria-label', 'Size ' + this.text + (available ? '' : ' — sold out'))
          .prop('disabled', !available)
          .on('click', function () { select.val(value).trigger('change'); });
        buttons.push({ value, button });
        group.append(button);
      });
      // Enhancement runs only after the native form is present and initialized.
      select.after(group);
      form.addClass('haya-size-enhanced');
      function updateSelection() {
        buttons.forEach(({ value, button }) => button.attr('aria-pressed', select.val() === value ? 'true' : 'false'));
      }
      select.on('change', updateSelection);
      form.on('reset_data found_variation', updateSelection);
      updateSelection();
    });
  });
})(jQuery);
