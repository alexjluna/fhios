# Visual Layout Suite (VLSuite)

VLSuite is the content editing experience on top of Site Builder that ambitious site builders are waiting for, providing an out of the box editing experience that takes Layout Builder to the next level.

VLSuite's goal is clear: **deliver maximum value in the shortest possible time** when it comes to site and content building. For this, you can enable the **VLSuite Shuttle**. This will enable all the required modules needed for site builders to start creating landing pages from scratch without needing technical knowledge.

A set of highly configurable layouts (1-4 columns + slider), a set of basic blocks (Image, Icon, Text, CTA, Local Video & Remote Video Youtube, Vimeo), also other compound collection blocks with extensible preset variants & customizable, like Hero, Card, Gallery & Statement / Quote.
And a content type ready to use any of these layouts and blocks will be available out of the box.

With collection blocks you will also find a preset of glossaries inside library.

You will be able to use all that as a reference to extend it, modify it after initial installation & apply to any other entity using layout builder.

Although this site editing experience out of the box is available thanks to the utility classes provided by Bootstrap 5, you can use any other theme or CSS framework as long as the utility classes for each functionality you use are available. VLSuite adds theme classes for you avoiding having to override templates in most of cases, also it uses an identifiers base system that allows you to change clasees or extend it without having to touch any code.

Besides you will be able to see a live preview of any utility applied directly just whit overing each utility option, and apply it just clicking on it.

Additionally, developers may extend the content authoring experience by adding custom layouts or blocks to have them ready to be used by Content Editors within a few clicks. Also use any other approach block for specific use cases if you need (contrib or custom).

## Key features
- Built upon Drupal Core’s Layout builder.
- What You See is What You Get experience at the layout and content level, including appearance variants.
- Editor experience greatly improved over what core provides.
- Live preview of appearance variants applied by utitlities
- Reusable section or complete layouts from your site using layout library.
- Permissions allow different levels of content editing for users (each utility can be marked as "Advanced" or not).
- No distribution, profile or theme dependency.
- Optional dependency on Bootstrap 5 to allow out of the box functionalities.
- Compatible with other approaches: if it works with Layout Builder it should work with VLSuite.
- Includes all needed configuration just to start using it by your editor just assigning role.
- Easy to extend.


## How it works?

**Utility classes concept**
CSS utility classes are self-descriptive classes that are applied to the markup to achieve a specific style (layout, text properties, backgrounds, element styles, etc). VLSuite injects the required utility classes in the markup according to the site builders needs.

Since VLSuite uses identifiers for the utility classes you can switch the actual CSS classes used to implement a certain functionality without editing all previously created content. This allows for a great flexibility and independence from the theme or CSS framework used.

If used on a Bootstrap 5 based theme, most utility classes are already available in the VLSuite, adjust them to fit your theme after initial installation otherwise.

**Drupal blocks**
Visual Layout Suite ships with a highly appearance customizable set of basic blocks such as Text, CTA, Image, Link and Video (local/remote) ready to use to create content.

Also includes also other compound collection blocks with extensible preset variants & customizable, like Hero, Card, Gallery & Statement / Quote.

With collection blocks you will also find a preset of glossaries for each of them inside library that editors can use as reference or as a base for your own.

You can easily set up a new custom block for your project, and within a few clicks, have it ready to be themed using any of the utility classes previously defined.

## Installation
**Please follow these steps carefully to avoid wrong setup, check it again if you experience any inconvenience.**

- Highly recommended using composer to get all dependencies, just use suggested command below (recommended using 1.1.x version to get live preview, collection blocks & many other new features).
- Make sure your site can use patches; Require "cweagans/composer-patches" & set "enable-patching: true" or apply them by your own (see composer.json inside module in that case). This is a required step to warantee best experience.
- If you use a bootstrap 5 based theme, make sure "container" class is not added into pages of content using layout builder & VLSuite. Editor will be able to choose if he wants an "Edge to edge" section for each VLSuite section. Floating UI may work incorrectly if you omit this step.
- Otherwhise using other framework just adjust utility clases by UI or yml to your theme.
- Recommended enabling "VLSuite Shuttle" for faster initial setup (all dependencies, configurations & all requirements are automated). Or enable just modules with features of your choice. That single module will be auto-disabled after doing his job.
- Customize any config or provided preset of everytning provided at your choice if you need it (utility classes, collection blocks view modes, medias to be used as background among other).
- If you don't need all landings to have same content top & botom structure disable "Full content bottom (VLSuite)" & "Full content top (VLSuite)" displays in "/admin/structure/types/manage/vlsuite_landing/display".
- Assign VLSuite permissions and/or role to users of your choice & start creating awesome highly customizable content.
