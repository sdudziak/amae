=== Splide Carousel Block ===
Contributors:      cloudcatch, dkjensen
Tags:              block, carousel, slider, splide, full-site-editing
Tested up to:      6.8
Stable tag:        1.7.0
License:           MIT
Requires PHP:      7.0
Requires at least: 6.5
Donate link:       https://www.buymeacoffee.com/dkjensen

Carousel block using Splide to create flexible and accessible sliders

== Description ==

The Splide Carousel block introduces the capability to create carousels/sliders within the block editor. Each carousel may contain an infinite number of slides, with each slides containing their own inner blocks.

Use the Splide Carousel block to create image carousels, testimonial rotators, and more in Gutenberg!

[View Demo](https://splide-demo.cloudcatch.io/)

== Options ==

* Arrows Navigation
* Dots Pagination
* Autoplay
* Slide and Fade Transitions
* Infinite Loop
* API to control the carousel using JavaScript
* Fixed Slide Width / Height
* Variable Slide Width / Height
* Number of Slides Per Page
* Slide Spacing
* Video Support
* Auto Scroll
* Hash Navigation
* Lazyload
* FSE (Full Site Editing) Compatible
* Fully Accessible
* RTL Support
* Responsive
* No Google Lighthouse Issues
* Lightweight
* [&hellip;and more!](https://splidejs.com/guides/options/#options)

== Attribution ==

The Splide Carousel block uses the [Splide](https://splidejs.com/) library.

== Changelog ==

= 1.7.0 =
* Enhancement - Update Add New Slide button to work with native Inserter to support `cloudcatch/splide-carousel-item` variations / defaults

= 1.6.0 =
* Enhancement - Bump block apiVersion to 3
* Chore - Update dependencies and WordPress compatibility to 6.8

= 1.5.0 =
* Enhancement - Compatibility with WordPress 6.7

= 1.4.7 =
* Fix - Slides not visible in RTL direction mode

= 1.4.6 =
* Fix - Speed setting change causing unexpected / invalid content error

= 1.4.5 =
* **POTENTIAL BREAKING CHANGE:**
Now using the CSS class `.wp-block-splide-carousel` to encapsulate the carousel and its components.  If you have any custom styles applied to the carousel or its components, it's recommended to test this update in a staging environment before updating in production.

= 1.4.4 =
* Fix - Carousel JavaScript variable name duplicating "Carousel" in the name

= 1.4.3 =
* Fix - HTML Anchor not applying to carousel

= 1.4.2 =
* Fix - Prevent autoplay and auto scroll in block editor

= 1.4.1 =
* Docs - Update tested up to

= 1.4.0 =
* Enhancement - Improved editor UX
* Enhancement - Added ability to make entire slide clickable link

= 1.3.0 =
* Enhancement - Added Grid extension
* Enhancement - Added autoplay play/pause buttons

= 1.2.0 =

* Enhancement - Added Inspector Controls to avoid having to use JSON
* Enhancement - Exposed global variable to programatically control the carousel using the Splide API

= 1.1.0 =

* Enhancement - Ability to add photos in bulk when new block is created and when block has no slides

= 1.0.0 =

* Initial release

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/splide-carousel` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress

== Frequently Asked Questions ==

= Are there additional options I can use? =

Yes, under the Splide Carousel block Advanced Settings, you can provide a valid JSON string containing additional options [located here](https://splidejs.com/guides/options/).

e.g.: `{"focus":"center","trimSpace":false}`

= Can I set default carousel options globally? =

Yes, we need to listen to a JavaScript event that is fired when the carousel is ready, and then add the defaults.

`document.addEventListener( 'splideReady', function( e ) {
    e.detail.defaults = {
        arrowPath: 'M30.2 19.4L11 .2c-.3-.3-.9-.3-1.2 0-.3.3-.3.8 0 1.2L28.4 20 9.8 38.6c-.3.3-.3.9 0 1.2.3.3.9.3 1.2 0l19.2-19.2c.3-.3.3-.9 0-1.2z',
        perPage: 3
    };
} );`

= Can I control the carousel using JavaScript / API? =

Yes, each carousel is given a unique ID by default, or you can provide your own using the **HTML Anchor** field under the block Advanced Settings.

Each block exposes a global variable that follows this convention: `{ID}Carousel`. For example, if your carousel ID is **testimonials**, the global variable will be `testimonialsCarousel`.

Using this global variable, you can control the carousel programatically using the [Splide API](https://splidejs.com/guides/apis/).

**Example to navigate to the third slide:**

`testimonialsCarousel.Components.Controller.go(2);`

== Screenshots ==

1. Block editor view
2. Block editor view
3. Front end view
4. Front end view
5. Block controls
