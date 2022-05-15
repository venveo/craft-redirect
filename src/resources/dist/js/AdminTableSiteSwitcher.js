/** global: Craft */
/** global: Garnish */
/** global: $ */

if (typeof Craft.Redirects === typeof undefined) {
    Craft.Redirects = {};
}

(function($) {
    Craft.Redirects.AdminTableSiteSwitcher = Garnish.Base.extend(
        {
            $siteMenuButton: null,
            adminTableVm: null,
            siteMenu: null,
            siteId: null,


            init: function(siteMenuSelector, adminTableVm, siteId) {
                this.$siteMenuBtn = $(siteMenuSelector).find('.sitemenubtn:first');
                if (!this.$siteMenuBtn.length) {
                    return;
                }
                this.adminTableVm = adminTableVm;

                this.siteMenu = this.$siteMenuBtn.menubtn().data('menubtn').menu;


                // Figure out the initial site
                var $option = this.siteMenu.$options.filter('.sel:first');

                if (!$option.length) {
                    $option = this.siteMenu.$options.first();
                }

                this.siteMenu.on('optionselect', $.proxy(this, '_handleSiteChange'));

                this.trigger('afterInit');
            },

            _handleSiteChange: function(ev) {
                this.siteMenu.$options.removeClass('sel');
                var $option = $(ev.selectedOption).addClass('sel');
                this.$siteMenuBtn.html($option.html());
                this._setSite($option.data('site-id'));
            },

            _setSite: function(siteId) {
                var currentUrl = this.adminTableVm.$children[0].$props.tableDataEndpoint;

                this.adminTableVm.$children[0].$props.tableDataEndpoint = Craft.getActionUrl('vredirect/catch-all/hits-table?siteId='+ Craft.siteId);

                // https://stackoverflow.com/questions/1090948/change-url-parameters/10997390#10997390
                var newAdditionalURL = "";
                var tempArray = currentUrl.split("?");
                var baseURL = tempArray[0];
                var additionalURL = tempArray[1];
                var temp = "";
                if (additionalURL) {
                    tempArray = additionalURL.split("&");
                    for (var i=0; i<tempArray.length; i++){
                        if(tempArray[i].split('=')[0] != 'siteId'){
                            newAdditionalURL += temp + tempArray[i];
                            temp = "&";
                        }
                    }
                }

                var rows_txt = temp + "" + 'siteId' + "=" + siteId;
                var newUrl = baseURL + "?" + newAdditionalURL + rows_txt;

                this.adminTableVm.$children[0].$props.tableDataEndpoint = newUrl;
            },


        });
})(jQuery);
