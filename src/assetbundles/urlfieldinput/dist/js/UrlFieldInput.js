/** global: Craft */
/** global: Garnish */
/** global: $ */

(function($) {
    Craft.UrlFieldInput = Garnish.Base.extend(
        {
            siteSelectId: null,
            textInputId: null,

            $siteSelect: null,
            $textInput: null,
            $prefixContainer: null,

            selectedSite: null,

            init: function(siteSelectId, textInputId) {
                this.siteSelectId = siteSelectId;
                this.textInputId = textInputId;

                this.$siteSelect = $('#' + this.siteSelectId);
                this.$textInput = $('#' + this.textInputId);
                this.$prefixContainer = this.$textInput.find('.prefix').first();

                this.addListener(this.$siteSelect, 'change', function(ev) {
                    var siteId = $(ev.target)[0].value;
                    this.changeSite(siteId);
                });

                this.changeSite(this.$siteSelect[0].value);

                this.trigger('afterInit');
            },

            changeSite: function(siteId) {
                this.selectedSite = null;
                for (var i = 0; i < window.redirectEditableSiteData.length; i++) {
                    if (window.redirectEditableSiteData[i].id == siteId) {
                        this.selectedSite = window.redirectEditableSiteData[i];
                    }
                }
                if (!this.selectedSite) {
                    this.$prefixContainer.empty();
                    this.$prefixContainer.hide();
                    return;
                }

                this.$prefixContainer.show();
                this.$prefixContainer.text(this.selectedSite.baseUrl);

                console.log(this.selectedSite);
            }
        });
})(jQuery);
