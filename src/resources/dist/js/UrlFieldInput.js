/** global: Craft */
/** global: Garnish */
/** global: $ */
// noinspection JSVoidFunctionReturnValueUsed

if (typeof Craft.Redirects === typeof undefined) {
    Craft.Redirects = {};
}

(function ($) {
    Craft.Redirects.UrlFieldInput = Garnish.Base.extend(
        {
            $container: null,
            siteOptions: [],

            $siteSelect: null,
            $textInput: null,
            $prefixContainer: null,

            selectedSite: null,

            init: function (container, settings) {
                this.$container = $(container);
                this.setSettings(settings, Craft.Redirects.UrlFieldInput.defaults);

                this.$siteSelect = this.$container.find('.sites > select')
                this.$textInput = this.$container.find('.url')
                this.$prefixContainer = this.$container.find('.prefix')
                this.siteOptions = this.settings.siteOptions

                this.addListener(this.$siteSelect, 'change', function (ev) {
                    var siteId = $(ev.target)[0].value;
                    this.changeSite(siteId);
                });

                this.changeSite(this.$siteSelect[0].value);

                this.trigger('afterInit');
            },

            changeSite: function (siteId) {
                this.selectedSite = null;
                for (var i = 0; i < this.siteOptions.length; i++) {
                    if (this.siteOptions[i].id == siteId) {
                        this.selectedSite = this.siteOptions[i];
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
        },
        {
            defaults: {},
        });
})(jQuery);