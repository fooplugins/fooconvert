# WooCommerce Widget Template Catalog

## Purpose

This document defines WooCommerce-oriented FooConvert widget templates.

A template is a ready-made composition built from some mix of:

- new Woo-aware FooConvert dynamic blocks
- existing FooConvert blocks
- static content
- core Gutenberg blocks
- selected WooCommerce content blocks or shortcodes where safe

The purpose of this catalog is to identify which commerce ideas should ship as:

- a reusable template only
- a template powered by one or more new dynamic blocks
- a template that depends on both dynamic blocks and merchant-authored static content

## Template composition rules

### Prefer FooConvert dynamic blocks when:

- the template depends on live cart or order state
- the template needs distinct locked/unlocked/applied/error states
- the same behavior will be reused across multiple templates

### Prefer static/core content when:

- the value is mostly copy, trust framing, imagery, and CTA hierarchy
- the content does not depend on live WooCommerce state

### Use Woo blocks or shortcodes only when:

- the embedded block is lightweight and self-contained
- it does not assume a full cart or checkout page context
- the same output cannot be achieved more safely with a custom FooConvert block

## Template catalog

## 1. Add To Cart Unlock Flyout

### Type

`fc/flyout`

### Goal

Increase average order value immediately after add to cart.

### Suggested trigger

- `cart.add`
- optional product filter

### Suggested display rules

- product pages
- optionally cart pages

### Composition

- `fc/free-shipping-progress`
- `fc/product-recommendations`
- Heading
- Paragraph
- Buttons

### Static content

- offer framing
- brand tone
- CTA labels

### Dynamic content

- subtotal progress
- amount remaining
- free-shipping threshold label
- recommended filler products when available

### Optional Woo content

- Shortcode block for a merchant-curated product set if the dynamic recommendations block is not ready

### MVP notes

`fc/free-shipping-progress` is now available in PRO, so the core progress portion of this template is implementable today.

The block can now resolve the current cart on non-Blocks pages via the FooConvert Woo cart endpoint, so it is not limited to native Woo cart/checkout block surfaces.

The full merchandising version still depends on `fc/product-recommendations` or a shortcode-based fallback for filler products.

Current limit: on classic/non-Blocks pages the cart resolution is a one-shot REST snapshot, not a fully live cart-fragments bridge.

## 2. Cart Rescue Offer Popup

### Type

`fc/popup`

### Goal

Recover abandonment by giving the shopper a clear, limited-time incentive.

### Suggested trigger

- exit intent on cart
- delayed timer on cart
- cart.updated under threshold condition

### Suggested display rules

- cart page

### Composition

- `fc/cart-offer-unlock`
- `fc/apply-coupon`
- `fc/countdown`
- Heading
- Paragraph
- Buttons

### Static content

- urgency copy
- reward explanation
- terms microcopy

### Dynamic content

- unlock status
- coupon state
- countdown

### Optional Woo content

- none required

### MVP notes

This template should use `fc/apply-coupon` for direct WooCommerce application. If a merchant only wants a reveal/copy flow instead of direct application, that should be treated as a separate `fc/coupon` variant rather than the default implementation.

## 3. Checkout Reassurance Bar

### Type

`fc/bar`

### Goal

Reduce checkout hesitation with concise trust and delivery messaging.

### Suggested trigger

- page load

### Suggested display rules

- checkout page

### Composition

- `fc/delivery-promise`
- `fc/trust-badges` or static icon list
- Paragraph
- Link/Button to support or returns

### Static content

- guarantee text
- support access text
- payment reassurance

### Dynamic content

- delivery promise
- optional order cutoff countdown

### Optional Woo content

- avoid embedding full checkout blocks

### MVP notes

This should be concise and mobile-first. It is a reassurance template, not a second checkout form.

## 4. Bundle Boost Flyout

### Type

`fc/flyout`

### Goal

Increase average order value by encouraging a one-click bundle or accessory set.

### Suggested trigger

- add to cart
- product page CTA click
- time delayed on product page

### Suggested display rules

- product pages

### Composition

- `fc/bundle-builder`
- Heading
- Paragraph
- optional `fc/review-snapshot`

### Static content

- merchandising headline
- savings explanation

### Dynamic content

- selected bundle items
- bundle pricing/savings
- add-all CTA

### Optional Woo content

- merchant-curated product cards via Shortcode block as fallback

### MVP notes

This template should start with manually curated bundles instead of algorithmic bundling.

## 5. Product Page Trust Popup

### Type

`fc/popup`

### Goal

Resolve product hesitation at the moment of evaluation.

### Suggested trigger

- delayed timer on product page
- element visible after review section or add-to-cart form

### Suggested display rules

- product page

### Composition

- `fc/review-snapshot`
- `fc/delivery-promise`
- trust badge row
- Heading
- Paragraph
- CTA button

### Static content

- guarantee copy
- return policy summary
- payment reassurance

### Dynamic content

- ratings summary
- top quote
- delivery promise

### Optional Woo content

- optional merchant-selected customer image in Image block

### MVP notes

This template should be light on motion and heavy on clarity.

## 6. Exit Intent Coupon Capture Popup

### Type

`fc/popup`

### Goal

Capture leads from high-intent product or cart visitors in exchange for an offer.

### Suggested trigger

- exit intent

### Suggested display rules

- product page
- cart page
- category page

### Composition

- `fc/sign-up`
- `fc/apply-coupon` for direct apply flows
- Heading
- Paragraph
- optional hero image

### Static content

- value proposition
- privacy reassurance
- terms line

### Dynamic content

- lead capture
- coupon apply state

### Optional Woo content

- none required

### MVP notes

This template now has two clear variants:

- apply-after-signup uses `fc/apply-coupon`
- reveal/copy-after-signup uses `fc/coupon`

The direct-apply variant is now a valid first-class WooCommerce template, not a future placeholder.

## 7. Thank You Account Claim Flyout

### Type

`fc/flyout`

### Goal

Turn guest orders into account relationships.

### Suggested trigger

- page load
- optionally delayed on thank-you page

### Suggested display rules

- thank-you / order received context

### Composition

- `fc/post-purchase-account-claim`
- Heading
- Paragraph
- benefit list
- Button

### Static content

- benefits copy
- reassurance about saved details and order tracking

### Dynamic content

- guest-order awareness
- account claim CTA/state

### Optional Woo content

- none

### MVP notes

This is one of the best post-purchase templates to add once order-aware context is available.

## 8. Replenishment Reminder Bar

### Type

`fc/bar`

### Goal

Promote repeat purchases and subscription uptake.

### Suggested trigger

- page load on eligible product pages
- post-purchase lifecycle display where available

### Suggested display rules

- replenishable product pages
- thank-you page
- my account relevant views

### Composition

- `fc/reorder-or-subscribe`
- Heading
- Paragraph
- CTA button

### Static content

- reorder framing
- savings or convenience message

### Dynamic content

- cadence options
- reorder or subscribe CTA

### Optional Woo content

- none in v1

### MVP notes

This can initially target simple replenishment offers before deeper subscription integrations exist.

## 9. Shipping Reassurance Bar

### Type

`fc/bar`

### Goal

Clarify delivery and returns early with minimal runtime complexity.

### Suggested trigger

- page load

### Suggested display rules

- product pages
- cart page

### Composition

- static icon list
- Heading or Paragraph
- Button or text link

### Static content

- shipping speed
- returns policy
- support benefit

### Dynamic content

- none required in v1

### Optional Woo content

- none

### MVP notes

This is a template-only candidate unless dynamic delivery data becomes a strong requirement.

## 10. Sale Ends Soon Bar

### Type

`fc/bar`

### Goal

Add urgency to sale or campaign landing pages.

### Suggested trigger

- page load

### Suggested display rules

- sale categories
- campaign landing pages
- product pages in campaign

### Composition

- `fc/countdown`
- static promo copy
- CTA button

### Static content

- sale framing
- CTA copy

### Dynamic content

- countdown

### Optional Woo content

- none

### MVP notes

This is mostly already possible today and should be packaged as a commerce template.

## 11. Welcome Discount Popup

### Type

`fc/popup`

### Goal

Capture new visitor leads for first-order incentives.

### Suggested trigger

- exit intent
- timer
- scroll percent

### Suggested display rules

- homepage
- product pages
- category pages

### Composition

- `fc/sign-up`
- optional `fc/coupon`
- image
- heading
- paragraph

### Static content

- visual theme
- value proposition
- terms line

### Dynamic content

- lead capture
- coupon delivery or reveal

### Optional Woo content

- none

### MVP notes

This should remain simple and should not depend on heavy Woo runtime data.

## 12. Review Quote Flyout

### Type

`fc/flyout`

### Goal

Show product or brand trust proof with minimal complexity.

### Suggested trigger

- delayed timer
- product page engagement trigger

### Suggested display rules

- product pages
- cart page

### Composition

- static quote block
- `fc/review-snapshot` optional
- CTA button

### Static content

- featured quote
- shopper persona framing

### Dynamic content

- optional rating/count

### Optional Woo content

- none

### MVP notes

This can ship as a mostly static template before the review snapshot block is complete.

## 13. Returns And Guarantee Popup

### Type

`fc/popup`

### Goal

Address policy-based objections without forcing a navigation away from the buying flow.

### Suggested trigger

- element click from returns link
- time delay on product page

### Suggested display rules

- product pages
- checkout page

### Composition

- static headings
- perk list
- guarantee badges
- button or support link

### Static content

- return window
- warranty
- risk reversal copy

### Dynamic content

- optional none

### Optional Woo content

- none

### MVP notes

This is template-only unless policy data becomes centrally managed.

## 14. Gift Guide / Seasonal Offer Bar

### Type

`fc/bar`

### Goal

Promote seasonal merchandising and curated collections.

### Suggested trigger

- page load

### Suggested display rules

- seasonal landing pages
- homepage
- category pages

### Composition

- heading
- paragraph
- button
- optional product collection embed via Shortcode block

### Static content

- campaign headline
- seasonal positioning

### Dynamic content

- optional product collection embed

### Optional Woo content

- Shortcode block with Woo product shortcode

### MVP notes

This is a strong low-effort campaign template.

## 15. Order Support Flyout

### Type

`fc/flyout`

### Goal

Reduce checkout friction and support burden by answering common order questions in place.

### Suggested trigger

- click on help/support link
- checkout hesitation trigger later if implemented

### Suggested display rules

- checkout page
- cart page

### Composition

- FAQ text
- support contact button
- optional `fc/instant-answer`

### Static content

- top support questions
- contact hours
- reassurance

### Dynamic content

- optional context-aware answer card

### Optional Woo content

- none

### MVP notes

This should start as a static support template and only later absorb dynamic answer logic.

## Template rollout recommendations

## Highest priority template pack

- Add To Cart Unlock Flyout
- Cart Rescue Offer Popup
- Product Page Trust Popup
- Checkout Reassurance Bar
- Exit Intent Coupon Capture Popup

This pack covers:

- AOV lift
- cart recovery
- trust and hesitation reduction
- lead capture
- checkout reassurance

## Second wave template pack

- Bundle Boost Flyout
- Thank You Account Claim Flyout
- Replenishment Reminder Bar
- Review Quote Flyout
- Order Support Flyout

This pack expands into:

- bundles
- post-purchase relationship building
- repeat purchase
- customer support deflection

## Template-only launches that can ship early

These do not need new dynamic Woo blocks to be useful:

- Shipping Reassurance Bar
- Sale Ends Soon Bar
- Welcome Discount Popup
- Returns And Guarantee Popup
- Gift Guide / Seasonal Offer Bar
- Review Quote Flyout

## Templates that depend on new dynamic blocks

- Add To Cart Unlock Flyout
- Cart Rescue Offer Popup
- Checkout Reassurance Bar
- Bundle Boost Flyout
- Product Page Trust Popup
- Thank You Account Claim Flyout
- Replenishment Reminder Bar

## Woo block and shortcode guidance

## Recommended

- use core Shortcode block for Woo product shortcodes when simple product list embeds are enough
- use core Image, Heading, Paragraph, Buttons, Group, Columns, and List blocks heavily

## Use cautiously

- Woo product display blocks that render from explicit query or product references

## Avoid in widget templates

- full cart blocks
- full checkout blocks
- large account-area blocks
- blocks that assume page-level providers or route context

## Acceptance criteria for a good template

A WooCommerce widget template is worth shipping when it has:

- a clear commerce objective
- a narrow placement strategy
- a small number of dynamic dependencies
- mobile-safe content density
- reusable messaging that merchants can quickly customize

If a template needs many live data sources, many action states, and heavy business logic, split that behavior into one or more dynamic blocks first.
