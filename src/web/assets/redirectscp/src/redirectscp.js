// SCSS
import "./scss/redirectscp.scss";

// JS
(function ($) {
  if (typeof Craft.Redirects === typeof undefined) {
    Craft.Redirects = {};
  }
})(jQuery);

import "./js/AdminTableSiteSwitcher";
import "./js/CatchAllIndex";
import "./js/ElementRedirectSlideout";
import "./js/RedirectsIndex";
import "./js/UrlFieldInput";
