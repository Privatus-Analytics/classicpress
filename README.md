# Privatus Analytics for ClassicPress

Simple, private website analytics for ClassicPress. No cookies, no IP addresses stored, and bots left out of
your numbers.

**This plugin connects your site to Privatus Analytics, an outside service.** That is its main job. The plugin
is free. The service has a free plan and paid plans, so under the ClassicPress directory rules for software
sold as a service, this plugin is listed as **Premium**.

## Website stats should be easy

Google Analytics is slow and confusing. You click through menu after menu just to see how many people
visited. It sends what your visitors do to Google. In many countries it means showing a cookie pop-up (the box
that asks visitors to accept cookies), and many people click "reject", so you never see them at all. Bots then
pad the numbers that are left.

**Privatus Analytics is the simple, private way to see who visits your site.** It counts visits without cookies
and never stores IP addresses. Bots are filtered out before they are counted. Your data is never sold or
shared. You paste one code into this plugin and you are done.

## What you get

- **A dashboard you understand in seconds.** Visitors, top pages, where people came from and which countries
  they are in, all on one screen.
- **No cookie pop-up needed for our analytics.** Privatus Analytics sets no cookies and stores no personal
  data. Laws differ by country, so check your own legal advice.
- **Honest numbers.** Known bots, fake traffic and spam are left out on every plan, so you see real people.
- **A fast site.** The tracking script is about 3 KB and loads without blocking your page.
- **Sales you can see.** With WooCommerce, every order shows up as revenue. No names, emails or order numbers
  are ever sent.
- **Data you own.** We never sell it and never show you ads. You can pull it out any time through the API.

## Pricing

The plugin is free. The Privatus Analytics Free plan has 10,000 events a month and 3 sites, with no card. An
event is a pageview or another action you track. Paid plans add more events and more features. See
[the pricing page](https://privatusanalytics.com/pricing).

## What the plugin does

- One-field setup: paste your site ID (it starts with `pa_`).
- Leaves out your own visits. Administrators are left out by default, and you can pick other roles.
- WooCommerce purchase tracking with the order total, currency, number of items and whether a coupon was used.
  Order page addresses are cleaned before they are sent, so order numbers never show up in your reports.
- A `[privatus_optout]` shortcode that lets visitors choose not to be counted, or be counted again.
- Suggested privacy policy text under Settings, Privacy.
- A card on the admin dashboard with a button that opens your Privatus Analytics dashboard.
- Every tracker option for advanced sites: single-page-app mode, manual pageviews, extra modules, allowed
  domains, excluded paths, path masks, extra query parameters, canonical URLs and hash routing.
- Nothing is written to the visitor's device, except the opt-out choice a visitor makes.

## Installation

1. Download `privatus-analytics-vX.Y.Z.zip` from the
   [latest release](https://github.com/Privatus-Analytics/classicpress/releases/latest).
2. In your admin area, go to **Plugins, Add New, Upload Plugin**, choose the zip and press **Install Now**.
3. Press **Activate Plugin**.
4. [Create a free Privatus Analytics account](https://privatusanalytics.com) and copy your site ID.
5. Go to **Settings, Privatus Analytics**, paste your site ID and save. Your first visitors show up within
   minutes.

To get updates inside ClassicPress, install the
[ClassicPress Directory Integration](https://directory.classicpress.net/plugins/classicpress-directory-integration/)
plugin.

## External service and your data

This plugin relies on the Privatus Analytics service, run by Two Phase LLC (Wyoming, USA).

**What it is for:** counting visits and sales on your site and showing them in your Privatus Analytics
dashboard.

**When:** nothing is loaded or sent until you enter a site ID in Settings, Privatus Analytics. Saving the site
ID is your opt-in.

**What is loaded and sent:** after you save a site ID, every public page loads the script
`https://privatusstats.com/js/pa.js` in the visitor's browser. That script sends these things to
`https://privatusstats.com/api/event`:

- the page address (query parameters are removed, except campaign tags and any you allow)
- the referrer, the browser language, the screen width and the time zone name
- the names and details of events (for WooCommerce: the order total, currency, item count and a coupon flag)
- your site ID

The visitor's IP address and browser details are used in memory only, mixed with a random value that changes
every day. This counts unique visits and spots bots. They are never stored. The visitor's country comes from
the country code that Cloudflare adds to each request. Nothing is sent when the visitor has opted out, when the
visitor is a logged-in user with an excluded role, or in the admin area. If you change the script host setting,
the same data goes to the host you enter.

**Optional modules:** if you turn on the engagement, automatic events, page speed, click map or survey modules,
the script loads the matching file from the same host, such as `https://privatusstats.com/js/pa.clicks.js`.

**The dashboard card** is a plain link. It makes no request from your server and embeds nothing.

- Privacy policy: https://privatusanalytics.com/legal/privacy
- Terms of service: https://privatusanalytics.com/legal/terms

## Frequently asked questions

**Does the plugin work without a Privatus Analytics account?**
No. The plugin adds the tracking script. Your reports live in your Privatus Analytics account.

**Where do I find my site ID?**
In your Privatus Analytics dashboard under Site settings, then Setup. It looks like `pa_AbC123`.

**Will it slow down my site?**
No. The script is about 3 KB and loads with `defer`, so it waits until your page is ready.

**Will my own visits be counted?**
Not if you are logged in with an excluded role. Administrators are excluded by default.

**Can I change the script tag from code?**
Yes. The `privatus_analytics_should_track` filter (true or false) skips tracking on a request. The
`privatus_analytics_script_attributes` filter (an array) changes the script tag attributes.

**What is removed when I delete the plugin?**
Its settings and the per-order purchase marker. Your data in Privatus Analytics is not touched.

## For developers

- Needs ClassicPress 2.0 or newer and PHP 7.4 or newer.
- The plugin lives in [`privatus-analytics/`](privatus-analytics/). Its folder name and main file match, as the
  ClassicPress directory asks.
- ClassicPress builds the tag for an enqueued script by hand, so the `defer` and `data-*` attributes are added
  in the `script_loader_tag` filter. Every value is escaped with `esc_attr()`.
- CI runs a PHP syntax check (PHP 7.4 to 8.4), the WordPress Coding Standards with PHP 7.4 compatibility, and
  builds the release zip.
- To release: bump `Version` in `privatus-analytics/privatus-analytics.php` and `PRIVATUS_ANALYTICS_VERSION`,
  then publish a GitHub release with the tag `vX.Y.Z`. The release workflow attaches
  `privatus-analytics-vX.Y.Z.zip`, which unpacks to a `privatus-analytics/` folder.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE) and [LICENSE-GPL-2.0.txt](LICENSE-GPL-2.0.txt).
