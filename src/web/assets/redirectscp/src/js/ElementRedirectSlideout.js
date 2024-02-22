/** global: Craft */
/** global: Garnish */
/** global: $ */
if (typeof Craft.Redirects === typeof undefined) {
  Craft.Redirects = {};
}

Craft.Redirects.ElementRedirectSlideout = Garnish.Base.extend({
  elementId: null,
  siteId: null,
  $openDialogButton: null,

  init: function (elementId, siteId, settings) {
    // Param mapping
    if (typeof settings === "undefined" && $.isPlainObject(elementId)) {
      // (settings)
      settings = elementId;
      elementId = null;
    }

    this.elementId = elementId;
    this.siteId = siteId;
    this.setSettings(
      settings,
      Craft.Redirects.ElementRedirectSlideout.defaults
    );

    this.$openDialogButton = $("#redirect-slideout-trigger > button");

    this.addListener(this.$openDialogButton, "click", (ev) => {
      ev.preventDefault();
      let cancelToken = axios.CancelToken.source();
      Craft.sendActionRequest(
        "GET",
        Craft.getActionUrl(
          "vredirect/element-slideouts/get-element-view-html",
          {
            elementId: this.elementId,
            siteId: this.siteId,
          }
        ),
        {
          cancelToken: cancelToken.token,
        }
      ).then((response) => {
        cancelToken = null;
        new Craft.Slideout(response.data);
      });
      Garnish.$win.on("beforeunload", () => {
        if (cancelToken) {
          cancelToken.cancel();
        }
      });
    });
  },
});
