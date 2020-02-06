/** global: Craft */
/** global: Garnish */
/** global: $ */

(function($) {
    /** global: Craft */
    /** global: Garnish */
    /**
     * Matrix input class
     */
    Craft.UrlFieldInput = Garnish.Base.extend(
        {
            siteSelectId: null,
            textInputId: null,

            $siteSelect: null,
            $textInput: null,

            selectedSite: null,

            init: function(siteSelectId, textInputId) {
                this.siteSelectId = siteSelectId;
                this.textInputId = textInputId;

                this.$siteSelect = $('#' + this.siteSelectId);
                this.$textInput = $('#' + this.textInputId);

                this.addListener(this.$siteSelect, 'change', function(ev) {
                    var siteId = $(ev.target)[0].value;
                    this.changeSite(siteId);
                });

                this.changeSite(this.$siteSelect[0].value);

                this.trigger('afterInit');
            },

            changeSite: function(siteId) {
                for (var i = 0; i < window.redirectEditableSiteData.length; i++) {
                    if (window.redirectEditableSiteData[i].id == siteId) {
                        this.selectedSite = Craft.sites[i];
                    }
                }
                if (!this.selectedSite) {
                    alert('Site not found');
                    return;
                }

                console.log(this.selectedSite);
            }
        });
})(jQuery);
