# WooCommerce Block Catalog

## Purpose

This document defines the WooCommerce-oriented dynamic blocks worth building for FooConvert.

The goal is to separate:

- reusable dynamic blocks that should become first-class FooConvert blocks
- upgrades to existing FooConvert blocks
- support blocks that make widget templates easier to compose

These blocks are intended to run inside existing FooConvert widget types:

- `fc-bar`
- `fc-flyout`
- `fc-popup`

They should work alongside existing content blocks, core Gutenberg blocks, and selective WooCommerce content embeds.

## Working assumptions

### Content model

FooConvert widget content areas already support flexible `InnerBlocks` composition. That means the new blocks can be mixed with:

- core blocks such as Heading, Paragraph, List, Buttons, Image, Group, Columns, and Shortcode
- existing FooConvert blocks such as `fc/countdown`, `fc/coupon`, and `fc/sign-up`
- a limited subset of WooCommerce content blocks or shortcodes where the block does not require a full page-level cart or checkout context

### WooCommerce integration model

The current plugin already has a useful foundation:

- WooCommerce page display rules
- WooCommerce open triggers for `cart.add` and `cart.updated`
- frontend subscriptions to Woo Blocks data stores
- server-side WooCommerce sale attribution

The new blocks should build on that model instead of inventing a parallel runtime.

### Block taxonomy

Use three categories:

1. Commerce state blocks
   These read product, cart, checkout, account, or order state.

2. Commerce action blocks
   These trigger a Woo action such as applying a coupon, adding products, or starting account creation.

3. Commerce reassurance blocks
   These reduce hesitation using proof, delivery information, policy, or support guidance.

## Existing blocks to evolve

These blocks already exist and are still part of the WooCommerce widget toolkit.

### `fc/coupon`

Current role:

- copy code
- log conversion
- optionally close widget
- optionally redirect

Current scope:

- remains the clipboard-oriented coupon block
- does not own Woo apply behavior anymore
- should be used for reveal/copy/promo-code handoff patterns

Notes:

- direct Woo coupon application now lives in the standalone PRO block `fc/apply-coupon`
- future work on `fc/coupon` should stay focused on copy/reveal UX, not mixed copy/apply modes

### `fc/sign-up`

Current role:

- capture email and optional name
- create a lead
- log conversion

Upgrade path:

- support coupon-after-signup flows
- support checkout-aware prefill or billing field sync
- support post-purchase account claim and marketing opt-in variants

### `fc/countdown`

Current role:

- generic FOMO timer
- fixed timestamp timer

Upgrade path:

- support cart expiry windows
- support coupon expiry windows
- support product sale expiry and campaign windows

### `fc/apply-coupon`

Current role:

- PRO-only WooCommerce action block
- applies a selected WooCommerce coupon directly from a FooConvert widget
- supports close-on-apply and redirect-on-apply flows
- shares the visual language of `fc/coupon` but is apply-only

Current implementation:

- registered as `fc/apply-coupon`
- rendered as the custom element `fc-apply-coupon`
- registered from PRO so saved widget content does not depend on `fc/coupon` apply mode
- uses a WooCommerce coupon selector in the editor rather than manual freeform coupon entry

Current editor behavior:

- merchant selects a published WooCommerce coupon using async search
- search is powered by `GET /fooconvert/v1/woocommerce/coupons?search=<term>`
- search is gated by the Woo capability `edit_shop_coupons`
- selected coupon persists both the coupon ID and coupon code
- button text, icon, layout, label visibility, text alignment, close-on-apply, and redirect behavior are configurable

Current runtime behavior:

- applies through Woo Blocks / Store API when available
- falls back to classic checkout and classic cart coupon endpoints when needed
- queues coupon handoff in session storage plus query param when the current page cannot apply immediately
- shows pending, success, and error result states
- supports redirect destinations of cart, checkout, or a custom URL

Known limits of the current implementation:

- single coupon only
- apply only, not remove
- searches published coupons by code
- no freeform manual coupon entry in v1

## New dynamic blocks

## 1. `fc/free-shipping-progress`

### Category

Commerce state block

### Purpose

Show the shopper how close they are to free shipping and what action unlocks it.

### Why it should be a block

This is not just template copy. It needs live cart data, threshold math, currency formatting, and conditional state changes.

### Primary placements

- product page flyout after add to cart
- cart bar
- cart flyout
- checkout reassurance bar

### Core inputs

- cart subtotal
- free shipping threshold
- currency code and formatting
- optional item count
- optional excluded shipping classes or destinations

### UI states

- locked: "You're $12 away from free shipping"
- almost there: progress emphasis
- unlocked: "Free shipping unlocked"
- unavailable: hidden or fallback copy when threshold is unknown

### Optional settings

- progress bar on/off
- show threshold label on/off
- unlocked CTA copy
- recommended filler products on/off
- minimum and maximum visibility ranges

### Current implementation

- shipped as a standalone PRO block
- registered as `fc/free-shipping-progress`
- rendered as the custom element `fc-free-shipping-progress`
- reads live subtotal data from the existing Woo Blocks cart store runtime
- uses a merchant-configured threshold amount instead of auto-reading shipping zones
- supports `locked`, `almost`, `unlocked`, and `unavailable` states
- supports message tokens `{remaining}`, `{threshold}`, and `{subtotal}`
- shows fallback copy for `unavailable` instead of hiding
- supports optional progress bar and threshold label toggles
- exposes editor preview states for styling without persisting runtime state

### Useful triggers and rules

- display on cart and checkout pages
- open after `cart.add`
- open on `cart.updated`
- only show when threshold not yet reached

### MVP

- read subtotal from Woo cart state
- compute amount remaining
- show progress and unlocked state
- support merchant-configured threshold fallback

### V2

- auto-read shipping zone thresholds where feasible
- support per-country messaging
- support filler product suggestions
- support gift threshold and other unlock modes

### Dependencies

- Woo cart store access
- currency formatting helper

### Known limits of the current implementation

- PRO-only
- live state depends on Woo Blocks cart store availability
- no classic cart-fragments bridge in v1
- no auto-read shipping zone thresholds in v1
- no per-country messaging in v1
- no filler product suggestions in v1
- no excluded shipping class or destination logic in v1

## 2. `fc/cart-offer-unlock`

### Category

Commerce state block

### Purpose

Show progress toward a merchant-defined reward, not limited to shipping.

### Why it should be a block

Merchants will want one generalized unlock system for:

- free gift
- discount
- free shipping
- bonus sample
- priority support

### Primary placements

- cart popup
- cart flyout
- checkout bar

### Core inputs

- subtotal
- item count
- coupon state
- included product IDs
- included product categories

### Reward types

- threshold reward
- category-presence reward
- coupon-required reward
- bundle-completion reward

### UI states

- locked
- unlocked
- applied
- expired

### MVP

- subtotal and item-count based unlock logic
- configurable message templates
- optional CTA button

### V2

- category/product logic
- reward badge output
- integration with coupon application block

### Dependencies

- Woo cart data
- optional shared unlock-rules utility

## 3. `fc/apply-coupon`

### Category

Commerce action block

### Purpose

Apply a WooCommerce coupon directly from a FooConvert widget.

### Why it should be a block

The existing copy-oriented coupon block is a clipboard helper. Applying a coupon requires Woo-aware runtime logic, editor-side coupon lookup, and page-context-specific fallbacks.

### Relationship to existing blocks

This is now a standalone PRO sibling to `fc/coupon`.

- `fc/coupon` stays copy-only
- `fc/apply-coupon` owns Woo apply behavior

### Primary placements

- exit intent coupon popup
- cart rescue popup
- checkout recovery bar
- post-signup offer popup

### Core inputs

- selected WooCommerce coupon
- selected coupon code and coupon ID
- optional override applied text
- apply destination
- close and redirect behavior

### Required actions

- apply coupon through Woo Blocks / Store API when available
- fallback to classic coupon form handling when needed
- queue handoff when coupon application must happen later in the journey

### UI states

- ready to apply
- applying
- applied
- invalid
- queued for handoff

### MVP

- shipped as a standalone PRO block
- apply coupon
- async coupon selector with search
- show success, pending, and error states
- support close on success
- support redirect after success or queued handoff

### V2

- remove coupon
- auto-apply on widget open
- show already-applied state
- show compatibility or stacking guidance
- multi-coupon support where store rules allow it

### Dependencies

- Woo cart store
- classic Woo coupon AJAX endpoints
- FooConvert session handoff utility
- admin REST coupon search endpoint

## 4. `fc/delivery-promise`

### Category

Commerce reassurance block

### Purpose

Reduce shipping hesitation by showing arrival expectations and fulfillment benefits earlier in the journey.

### Primary placements

- product page reassurance popup
- cart bar
- checkout bar

### Core inputs

- estimated delivery window
- pickup availability
- shipping speed text
- return window
- destination-aware fallback text

### Output patterns

- "Order in the next 2h 14m for dispatch today"
- "Arrives Tue, Apr 8 - Thu, Apr 10"
- "Local pickup available today"
- "30-day returns"

### MVP

- merchant-configured static/fallback content with dynamic date formatting
- optional countdown-to-cutoff display

### V2

- integration with real shipping rules
- destination-aware messaging
- pickup-location awareness

### Dependencies

- may begin as partially dynamic plus merchant-configured content

## 5. `fc/review-snapshot`

### Category

Commerce reassurance block

### Purpose

Summarize product trust signals in a compact format suitable for widgets.

### Primary placements

- product page trust popup
- cart reassurance flyout
- checkout sidebar bar/flyout

### Core inputs

- average rating
- review count
- optional top quote
- optional badges

### Output patterns

- stars + count
- one highlighted quote
- 2-4 trust bullets

### MVP

- merchant-entered summary with optional product-linked rating/count inputs

### V2

- pull from Woo review data
- AI summary of common praise and concern
- filter by product variation where possible

### Dependencies

- product context resolver
- optional review adapter

## 6. `fc/product-recommendations`

### Category

Commerce state block

### Purpose

Show contextual product cards inside FooConvert widgets.

### Primary placements

- add-to-cart unlock flyout
- cart filler-product popup
- checkout cross-sell flyout
- thank-you reorder block

### Recommendation modes

- manually curated IDs
- accessories for current product
- fillers under a target price
- related products
- best sellers in category

### Card fields

- image
- title
- price
- rating optional
- add-to-cart CTA

### MVP

- merchant-selected products
- compact product card output
- add-to-cart links

### V2

- dynamic recommendation logic from current product/cart
- variation-aware CTAs
- stock/status filters

### Dependencies

- Woo product query utility

## 7. `fc/bundle-builder`

### Category

Commerce action block

### Purpose

Allow a shopper to add a starter bundle, complete-the-set selection, or accessory pack in one interaction.

### Primary placements

- product page flyout
- cart upsell popup

### Bundle modes

- fixed bundle
- optional add-ons
- required companion products

### Core behavior

- display line items
- show combined savings
- add all selected products to cart

### MVP

- merchant-defined bundle lines
- add-all CTA
- optional discount messaging

### V2

- variation selection
- quantity controls
- compare standalone total vs bundle total

### Dependencies

- multi-add-to-cart action support

## 8. `fc/post-purchase-account-claim`

### Category

Commerce action block

### Purpose

Convert guest buyers into account holders immediately after purchase.

### Primary placements

- thank-you page flyout
- order status popup
- post-purchase bar

### Core inputs

- guest checkout state
- order number context
- account creation URL or action

### Benefits to surface

- track your order
- save your details
- faster checkout next time
- manage subscriptions or reorders

### MVP

- show only for guest orders
- CTA to create account / claim order
- configurable benefits list

### V2

- one-click claim flow
- login if existing email already has account
- loyalty join prompt

### Dependencies

- order or thank-you page context

## 9. `fc/reorder-or-subscribe`

### Category

Commerce action block

### Purpose

Encourage repeat purchase or subscription enrollment for replenishable products.

### Primary placements

- product page flyout
- thank-you page follow-up flyout
- account-area promotion

### Core inputs

- product ID
- purchase cadence options
- subscription URL or add-to-cart settings

### MVP

- buy once vs subscribe CTA split
- cadence selector text
- reorder CTA

### V2

- estimated run-out date
- past-order awareness
- subscription plugin compatibility

### Dependencies

- may require compatibility layer with Woo Subscriptions or equivalent

## 10. `fc/instant-answer`

### Category

Commerce reassurance block

### Purpose

Answer pre-purchase objections without sending the shopper away from the buying flow.

### Primary placements

- product page popup
- cart rescue flyout
- checkout reassurance popup

### Question types

- shipping
- sizing
- compatibility
- stock
- returns
- warranty

### MVP

- merchant-authored FAQ answer cards
- optionally switch card by product/category

### V2

- AI-assisted answer generation
- answer suggestions from product metadata and reviews

### Dependencies

- can start with a purely merchant-authored content model

## Support blocks

These are smaller utility blocks that may be worth creating if the catalog grows.

### `fc/trust-badges`

Simple payment, guarantee, returns, and security badges with consistent styling.

### `fc/perk-list`

Compact icon-plus-text list for shipping, returns, pickup, support, or loyalty benefits.

### `fc/campaign-badge`

Small promotional label block for "Best Seller", "Free Gift", "Ships Today", or "Limited Drop".

These may not need to be v1 dynamic blocks if core blocks and theme styles can cover the same ground.

## Core block and Woo block usage inside FooConvert

## Safe to use freely

- Heading
- Paragraph
- List
- Buttons
- Image
- Group
- Columns
- Separator
- Shortcode

## Safe with care

- Woo product grid / product collection style blocks when they can render from explicit query context
- Woo shortcodes in the Shortcode block for product lists or add-to-cart links

## Avoid inside widgets

- full cart block
- full checkout block
- blocks that assume page-level checkout providers or route context
- heavy account/dashboard blocks

## Recommended implementation order

## Implemented today

- `fc/free-shipping-progress`
- `fc/apply-coupon`

## Phase 1

- `fc/review-snapshot`
- `fc/product-recommendations`

These complete the next most useful widget templates on top of the blocks already shipped.

## Phase 2

- `fc/cart-offer-unlock`
- `fc/delivery-promise`
- `fc/bundle-builder`

These deepen cart and checkout merchandising.

## Phase 3

- `fc/post-purchase-account-claim`
- `fc/reorder-or-subscribe`
- `fc/instant-answer`

These expand into retention, lifecycle, and higher-consideration commerce flows.

## Acceptance criteria for block candidates

Promote a concept into a true FooConvert dynamic block when it needs at least two of the following:

- live Woo data
- distinct commerce states
- merchant configuration beyond plain copy/styling
- reuse across multiple widget templates
- action handling beyond ordinary links/buttons

If it does not meet that threshold, ship it as a widget template built from:

- static content
- core blocks
- existing FooConvert blocks
