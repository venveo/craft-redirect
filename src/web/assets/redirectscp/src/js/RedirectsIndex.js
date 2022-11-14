/** global: Craft */
/** global: Garnish */
// noinspection JSVoidFunctionReturnValueUsed

Craft.Redirects.RedirectsIndex = Craft.BaseElementIndex.extend({
  $newRedirectBtn: null,
  forceCreateInSlideout: true,

  init: function (elementType, $container, settings) {
    this.on("selectSource", this.updateButton.bind(this));
    this.on("selectSite", this.updateButton.bind(this));
    this.base(elementType, $container, settings);
  },

  updateButton: function () {
    // Remove the old button, if there is one
    if (this.$newRedirectBtn) {
      this.$newRedirectBtn.remove();
    }

    this.$newRedirectBtn = Craft.ui
      .createButton({
        label: Craft.t("vredirect", "New redirect"),
        spinner: true,
      })
      .addClass("submit add icon btngroup-btn-last");

    this.addListener(this.$newRedirectBtn, "click mousedown", () => {
      const sourceId = this.$source.data("id");
      this._createRedirect(sourceId);
    });

    this.addButton(this.$newRedirectBtn);
  },

  _createRedirect: function (groupId) {
    if (this.$newRedirectBtn.hasClass("loading")) {
      return;
    }

    this.$newRedirectBtn.addClass("loading");

    Craft.sendActionRequest("POST", "vredirect/redirects/create", {
      data: {
        siteId: this.siteId,
        group: groupId,
      },
    })
      .then(({ data }) => {
        // NOTE:
        if (!this.forceCreateInSlideout && this.settings.context === "index") {
          document.location.href = Craft.getUrl(data.cpEditUrl, { fresh: 1 });
        } else {
          const slideout = Craft.createElementEditor(this.elementType, {
            siteId: this.siteId,
            elementId: data.redirect.id,
            draftId: data.redirect.draftId,
            params: {
              fresh: 1,
            },
          });
          slideout.on("submit", () => {
            this.clearSearch();
            this.setSelectedSortAttribute("dateCreated", "desc");
            this.selectElementAfterUpdate(data.entry.id);
            this.updateElements();
          });
        }
      })
      .finally(() => {
        this.$newRedirectBtn.removeClass("loading");
      });
  },
});

Craft.registerElementIndexClass(
  "venveo\\redirect\\elements\\Redirect",
  Craft.Redirects.RedirectsIndex
);
