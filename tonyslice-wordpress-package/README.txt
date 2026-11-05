TONY SLICE WORDPRESS STAGING PACKAGE

This plugin brings the complete design and page structure from the approved preview into a WordPress staging site. It includes the shared responsive theme, navigation, consent-gated analytics, booking form, and all 24 preview pages. It does not change the production site or automatically replace any WordPress page.

INSTALL ON STAGING ONLY
1. In the staging WordPress dashboard, go to Plugins > Add New Plugin > Upload Plugin.
2. Upload tony-slice-staging.zip, choose Install Now, then Activate Plugin.
3. Create a staging page for each route below and set its template to Elementor Canvas (or another blank/full-width template without theme header/footer).
4. Add the listed shortcode to each page and publish it on staging. Keep the staging host set to discourage search indexing.
5. Keep existing pages unchanged. In particular, do not set the preview page as the production homepage.

PAGE ROUTES AND SHORTCODES
Use these page slugs and shortcodes so the preview's internal navigation resolves correctly:

  /tony-slice-preview/                         [tony_slice]
  /social-media/                               [tony_slice type="social-media"]
  /social-media/content-visits/                [tony_slice type="social-media-content-visits"]
  /social-media/monthly-management/            [tony_slice type="social-media-monthly-management"]
  /social-media/campaigns/                     [tony_slice type="social-media-campaigns"]
  /events/                                     [tony_slice type="events-hub"]
  /events/hosting/                             [tony_slice type="events-hosting"]
  /collaborate/                                [tony_slice type="collaborate"]
  /collaborate/restaurants/                    [tony_slice type="collaborate-restaurants"]
  /collaborate/hotels/                         [tony_slice type="collaborate-hotels"]
  /collaborate/brands/                         [tony_slice type="collaborate-brands"]
  /businesses/                                 [tony_slice type="businesses"]
  /businesses/fresh-fold-altoona/              [tony_slice type="businesses-fresh-fold-altoona"]
  /watch/                                      [tony_slice type="watch"]
  /food/                                       [tony_slice type="food"]
  /comedy/                                     [tony_slice type="comedy"]
  /about/                                      [tony_slice type="about"]
  /press/                                      [tony_slice type="press"]
  /book/                                       [tony_slice type="book"]
  /contact/                                    [tony_slice type="contact"]
  /sitemap/                                    [tony_slice type="sitemap"]

The package also retains the earlier standalone page shortcodes for compatibility:
  /events-private-parties/                     [tony_slice type="events"]
  /restaurant-promotions/                      [tony_slice type="restaurants"]
  /brand-partnerships/                         [tony_slice type="brands"]

The homepage preview is available at /tony-slice-preview/. The plugin rewrites internal home links to that staging page; other internal links use the routes above.
On the current staging site, /contact/ redirects to /contact-us/; the plugin supports both paths.

BOOKING
The booking form uses the tested Formspree endpoint https://formspree.io/f/xoejrbkl. A success message and booking_form_success event occur only after the endpoint returns a successful response. Test with a clearly marked test inquiry and confirm its receipt in the agreed inbox.

ANALYTICS
Google Analytics loads only after a visitor grants consent. Test with analytics consent enabled and check GA4 DebugView/Realtime for booking-button clicks, a successful test submission, and business links. No analytics loads before consent.

ROLLBACK
On staging, deactivate and delete the Tony Slice Staging Pages plugin. The original WordPress pages and homepage are not deleted or overwritten by the plugin.

NOTES
- The plugin is a staging preview; production deployment and homepage replacement remain manual steps after review and approval.
- The package intentionally does not update WordPress, themes, plugins, or the live site.
- Do not test with real customer data.
