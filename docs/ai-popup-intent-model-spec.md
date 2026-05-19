# AI Popup Intent Model Spec

## Problem Statement

FooConvert AI Popup Builder currently asks the model to return the render-adjacent `popup_draft` shape directly. That shape is useful to the editor because it contains `popup_type`, strategy fields, `trigger`, `root_attributes`, and recursive `content_blocks`, but it is a poor model-output contract. The model must emit deeply nested Gutenberg/block JSON, including exact block names, attributes, allowed children, FooConvert shell assumptions, and trigger details. Small syntax or nesting failures, such as a missing brace inside an `fc/countdown` block, invalidate the whole response before the server can normalize or preview it.

The future contract should ask the model for high-level popup intent and let FooConvert-owned PHP/JS compile that intent into the existing draft shape. The model should describe what the popup should do; FooConvert should decide how that becomes `root_attributes`, `content_blocks`, media references, triggers, and saved markup.

## Goals

- Replace nested block JSON generation as the preferred AI output with a compact `popup_intent` object.
- Keep the existing `popup_draft` response shape as the render/save contract consumed by `src/admin/ai-popup-builder/App.js`, `src/admin/ai-popup-builder/preview.js`, and `src/admin/ai-popup-builder/serializer.js`.
- Add a deterministic server/client normalization layer that compiles `popup_intent` into a sanitized `popup_draft`.
- Reduce invalid JSON and invalid block-tree failures by keeping model output shallow and bounded.
- Preserve current capabilities: popup bars, flyouts, overlays, triggers, image/background generation, brand-aware styling, templates, validation, media panel behavior, and incremental edit prompts.
- Support migration where current `popup_draft` responses and new `popup_intent` responses coexist.

## Non-Goals

- Do not change the saved popup block markup format. Final serialization should still produce `fc/bar`, `fc/flyout`, or canonical `fc/overlay` markup through the current serializer path.
- Do not remove `popup_draft` from REST responses in the first migration phase.
- Do not require the AI model to know exact Gutenberg block internals for normal popup layouts.
- Do not solve short-term malformed nested JSON repair bugs here. Those fixes are tracked separately.
- Do not make the intent model a public extension API until the compiler behavior stabilizes.

## Current System Notes

- `includes/AI/PopupBuilder/ChatService.php` builds prompt history, calls the AI provider, decodes JSON with `ResponseParser`, validates the current response contract, and returns `PopupBlueprint::sanitize_ai_response()`.
- `includes/AI/PopupBuilder/ResponseParser.php` currently expects top-level keys `assistant_message`, `clarifying_question`, `suggested_prompts`, `media_items`, and `popup_draft`. It also validates that a non-null `popup_draft` has required draft keys and at least one `content_blocks` item.
- `includes/AI/PopupBuilder/Blueprint/DraftNormalizer.php` owns the current draft schema, sanitization, trigger normalization, root attribute sanitization, content block filtering, template extraction, and draft validation.
- `includes/AI/PopupBuilder/Blueprint/Schema.php` forwards schema/contract methods from `DraftNormalizer` and exposes a filter for the assistant response schema.
- `includes/AI/PopupBuilder/RestController.php` accepts `popup_draft` in `/fooconvert/v1/ai-popup-builder/chat` and `/chat-stream`, sanitizes it before passing to `ChatService`, and returns the normalized response.
- `src/admin/ai-popup-builder/App.js` sends the current draft as `popup_draft`, accepts `response.popup_draft`, updates preview/details/media state, and saves metadata.
- `src/admin/ai-popup-builder/serializer.js` and `serializer-support.js` turn `popup_draft` into block markup by building root attributes, trigger config, and content blocks.

## Proposed Response Contract

The assistant response should allow either a new `popup_intent` or a legacy `popup_draft`.

```json
{
  "assistant_message": "I drafted an exit-intent discount popup.",
  "clarifying_question": "",
  "suggested_prompts": [
    "Make the CTA more urgent",
    "Use a shorter flyout layout"
  ],
  "media_items": [],
  "popup_intent": {
    "schema_version": 1,
    "popup_type": "popup",
    "goal": "recover abandoning shoppers",
    "audience": "first-time cart abandoners",
    "offer": {
      "type": "discount",
      "summary": "10% off today",
      "code": "SAVE10",
      "expires": "session"
    },
    "copy": {
      "headline": "Wait, take 10% off your order",
      "body": "Complete checkout today and save on everything in your cart.",
      "benefits": [
        "Instant discount at checkout",
        "Valid on your current cart"
      ],
      "cta": {
        "label": "Apply 10% Off",
        "url": "/checkout",
        "action": "apply_coupon"
      },
      "legal": "Offer valid for this session."
    },
    "conversion_components": [
      "headline",
      "body",
      "benefits",
      "coupon",
      "cta",
      "countdown"
    ],
    "countdown": {
      "enabled": true,
      "mode": "evergreen",
      "duration_minutes": 15,
      "label": "Offer ends soon"
    },
    "trigger": {
      "intent": "exit_intent",
      "delay_seconds": 5,
      "lifetime": "session",
      "frequency": "once"
    },
    "style": {
      "preset": "brand_clean_urgency",
      "tone": "confident",
      "density": "compact",
      "tokens": {
        "accent": "#D92D20",
        "background": "#FFFFFF",
        "text": "#111827"
      }
    },
    "layout": {
      "structure": "single_column",
      "template_preference": "",
      "emphasis": "offer"
    },
    "media": {
      "intent": "none",
      "placement": "background",
      "prompt": ""
    },
    "rationale": [
      "Exit intent matches shoppers about to leave.",
      "A single discount CTA keeps the popup focused."
    ],
    "notes": []
  },
  "popup_draft": null
}
```

### Top-Level Keys

- `assistant_message`: Same meaning as today. Concise user-visible summary.
- `clarifying_question`: Same meaning as today. Empty when a draft can be built.
- `suggested_prompts`: Same meaning as today. Incremental edits only.
- `media_items`: Same meaning as today. Server may overwrite with generated/listed media.
- `popup_intent`: Preferred model output. Object or `null`.
- `popup_draft`: Legacy/direct render draft. Object or `null`. During migration, server should return a compiled `popup_draft` to the client even when the model only emitted `popup_intent`.

### Intent Schema

```json
{
  "type": "object",
  "required": [
    "schema_version",
    "popup_type",
    "goal",
    "audience",
    "offer",
    "copy",
    "conversion_components",
    "trigger",
    "style",
    "layout",
    "media",
    "rationale",
    "notes"
  ],
  "additionalProperties": false,
  "properties": {
    "schema_version": { "type": "integer", "enum": [1] },
    "popup_type": { "type": "string", "enum": ["bar", "flyout", "popup"] },
    "goal": { "type": "string" },
    "audience": { "type": "string" },
    "offer": {
      "type": "object",
      "additionalProperties": false,
      "properties": {
        "type": { "type": "string", "enum": ["discount", "lead_magnet", "announcement", "cart_recovery", "product_recommendation", "free_shipping", "newsletter", "custom"] },
        "summary": { "type": "string" },
        "code": { "type": "string" },
        "expires": { "type": "string" },
        "value": { "type": "string" }
      }
    },
    "copy": {
      "type": "object",
      "additionalProperties": false,
      "properties": {
        "headline": { "type": "string" },
        "eyebrow": { "type": "string" },
        "body": { "type": "string" },
        "benefits": { "type": "array", "items": { "type": "string" } },
        "cta": {
          "type": "object",
          "additionalProperties": false,
          "properties": {
            "label": { "type": "string" },
            "url": { "type": "string" },
            "action": { "type": "string", "enum": ["link", "apply_coupon", "submit_email", "continue_checkout", "view_product", "custom"] }
          }
        },
        "secondary_cta": {
          "type": "object",
          "additionalProperties": false,
          "properties": {
            "label": { "type": "string" },
            "url": { "type": "string" }
          }
        },
        "legal": { "type": "string" }
      }
    },
    "conversion_components": {
      "type": "array",
      "items": { "type": "string", "enum": ["eyebrow", "headline", "body", "benefits", "image", "coupon", "form", "cta", "countdown", "social_proof", "product", "divider", "legal"] }
    },
    "countdown": {
      "type": "object",
      "additionalProperties": false,
      "properties": {
        "enabled": { "type": "boolean" },
        "mode": { "type": "string", "enum": ["evergreen", "fixed", "session"] },
        "duration_minutes": { "type": "integer" },
        "end_at": { "type": "string" },
        "label": { "type": "string" }
      }
    },
    "trigger": {
      "type": "object",
      "additionalProperties": false,
      "properties": {
        "intent": { "type": "string", "enum": ["immediate", "delay", "exit_intent", "scroll_percent", "anchor_click", "element_click", "element_visible", "cart_add", "cart_updated", "coupon_applied", "coupon_invalid", "checkout_error", "checkout_enter", "checkout_exit", "product_view", "product_high_intent"] },
        "delay_seconds": { "type": "integer" },
        "scroll_percent": { "type": "integer" },
        "selector": { "type": "string" },
        "ids": { "type": "array", "items": { "type": "string" } },
        "lifetime": { "type": "string", "enum": ["page", "session", "visit"] },
        "frequency": { "type": "string", "enum": ["once", "repeat"] }
      }
    },
    "style": {
      "type": "object",
      "additionalProperties": false,
      "properties": {
        "preset": { "type": "string" },
        "tone": { "type": "string" },
        "density": { "type": "string", "enum": ["compact", "standard", "expanded"] },
        "tokens": {
          "type": "object",
          "additionalProperties": false,
          "properties": {
            "accent": { "type": "string" },
            "background": { "type": "string" },
            "text": { "type": "string" },
            "button_background": { "type": "string" },
            "button_text": { "type": "string" }
          }
        }
      }
    },
    "layout": {
      "type": "object",
      "additionalProperties": false,
      "properties": {
        "structure": { "type": "string", "enum": ["single_column", "split", "stacked", "bar_inline", "flyout_card"] },
        "template_preference": { "type": "string" },
        "emphasis": { "type": "string", "enum": ["offer", "form", "product", "urgency", "message"] }
      }
    },
    "media": {
      "type": "object",
      "additionalProperties": false,
      "properties": {
        "intent": { "type": "string", "enum": ["none", "use_existing", "generate_image", "generate_background"] },
        "placement": { "type": "string", "enum": ["none", "inline", "background", "split_side"] },
        "attachment_id": { "type": "integer" },
        "url": { "type": "string" },
        "alt": { "type": "string" },
        "prompt": { "type": "string" }
      }
    },
    "rationale": { "type": "array", "items": { "type": "string" } },
    "notes": { "type": "array", "items": { "type": "string" } }
  }
}
```

## Additional Examples

### Email Capture Flyout

```json
{
  "assistant_message": "I drafted a compact lead-capture flyout.",
  "clarifying_question": "",
  "suggested_prompts": ["Make it warmer", "Add a stronger benefit list"],
  "media_items": [],
  "popup_intent": {
    "schema_version": 1,
    "popup_type": "flyout",
    "goal": "grow the email list",
    "audience": "new visitors browsing product pages",
    "offer": { "type": "lead_magnet", "summary": "Join for early access and member offers", "code": "", "expires": "", "value": "" },
    "copy": {
      "headline": "Get first access to new drops",
      "eyebrow": "Members only",
      "body": "Sign up for launches, restocks, and subscriber-only offers.",
      "benefits": ["Early product alerts", "Private subscriber offers"],
      "cta": { "label": "Join the List", "url": "", "action": "submit_email" },
      "legal": "No spam. Unsubscribe anytime."
    },
    "conversion_components": ["eyebrow", "headline", "body", "benefits", "form", "legal"],
    "countdown": { "enabled": false, "mode": "session", "duration_minutes": 0, "end_at": "", "label": "" },
    "trigger": { "intent": "scroll_percent", "delay_seconds": 0, "scroll_percent": 45, "lifetime": "session", "frequency": "once" },
    "style": { "preset": "brand_clean", "tone": "friendly", "density": "compact", "tokens": {} },
    "layout": { "structure": "flyout_card", "template_preference": "", "emphasis": "form" },
    "media": { "intent": "none", "placement": "none", "prompt": "" },
    "rationale": ["Scroll depth indicates engagement before asking for email."],
    "notes": []
  },
  "popup_draft": null
}
```

### Shipping Bar

```json
{
  "assistant_message": "I drafted a compact free-shipping bar.",
  "clarifying_question": "",
  "suggested_prompts": ["Make the copy shorter", "Trigger it after cart updates"],
  "media_items": [],
  "popup_intent": {
    "schema_version": 1,
    "popup_type": "bar",
    "goal": "increase average order value",
    "audience": "active shoppers",
    "offer": { "type": "free_shipping", "summary": "Free shipping over $75", "code": "", "expires": "", "value": "$75 threshold" },
    "copy": {
      "headline": "Free shipping over $75",
      "body": "Add a little more to your cart and shipping is on us.",
      "benefits": [],
      "cta": { "label": "Keep Shopping", "url": "/shop", "action": "link" }
    },
    "conversion_components": ["headline", "body", "cta"],
    "countdown": { "enabled": false, "mode": "session", "duration_minutes": 0, "end_at": "", "label": "" },
    "trigger": { "intent": "delay", "delay_seconds": 4, "lifetime": "page", "frequency": "once" },
    "style": { "preset": "brand_bar", "tone": "direct", "density": "compact", "tokens": {} },
    "layout": { "structure": "bar_inline", "template_preference": "", "emphasis": "offer" },
    "media": { "intent": "none", "placement": "none", "prompt": "" },
    "rationale": ["A bar is visible without interrupting active shopping."],
    "notes": []
  },
  "popup_draft": null
}
```

## Intent to Draft Mapping

The compiler should produce the same sanitized draft shape returned by `DraftNormalizer::sanitize_popup_draft()`:

```json
{
  "title": "",
  "popup_type": "popup",
  "goal": "",
  "audience": "",
  "offer": "",
  "template_slug": "",
  "trigger": {},
  "root_attributes": {},
  "content_blocks": [],
  "conversion_rationale": [],
  "notes": []
}
```

### Field Mapping

- `title`: derive from `copy.headline`, capped to a short admin title. Fallback to `{Offer summary} Popup`.
- `popup_type`: normalize through the same builder taxonomy as `DraftNormalizer::normalize_builder_popup_type()`: `bar`, `flyout`, `popup`.
- `goal`, `audience`: copy directly after sanitization.
- `offer`: flatten from `offer.summary`, plus `offer.code` or `offer.value` when useful.
- `template_slug`: use `layout.template_preference` only when it matches a real bundled template for the selected `popup_type`; otherwise empty.
- `conversion_rationale`: copy from `rationale`, capped to current draft limits.
- `notes`: copy from `notes`, plus non-fatal compiler decisions such as "Requested countdown omitted because no supported countdown block is available."

### Trigger Mapping

Map `popup_intent.trigger.intent` to current trigger events accepted by `DraftNormalizer`:

- `immediate` -> `fc.immediate`
- `delay` -> `fc.timer.elapsed`, `where.seconds = delay_seconds`
- `exit_intent` -> `fc.exit_intent`, `where.delaySeconds = delay_seconds`
- `scroll_percent` -> `fc.scroll.percent`, `where.percent = scroll_percent`
- `anchor_click` -> `fc.anchor.click`, `where.ids = ids`
- `element_click` -> `fc.element.click`, `where.selector = selector`
- `element_visible` -> `fc.element.visible`, `where.ids = ids`
- `cart_add` -> `cart.add`
- `cart_updated` -> `cart.updated`
- `coupon_applied` -> `coupon.applied`
- `coupon_invalid` -> `coupon.invalid`
- `checkout_error` -> `checkout.error`
- `checkout_enter` -> `checkout.enter`
- `checkout_exit` -> `checkout.exit`
- `product_view` -> `product.view`
- `product_high_intent` -> `product.high_intent`

Compiler output should include both shortcut-compatible keys and normalized `steps`:

```json
{
  "type": "exit_intent",
  "event": "fc.exit_intent",
  "where": { "delaySeconds": 5 },
  "delay_seconds": 5,
  "scroll_percent": 20,
  "lifetime": "session",
  "frequency": "once",
  "steps": [
    { "event": "fc.exit_intent", "where": { "delaySeconds": 5 } }
  ]
}
```

Then pass through existing trigger sanitization so defaults remain centralized.

### Content Block Mapping

The compiler should generate a conservative block list from `conversion_components`, `copy`, `offer`, `countdown`, `media`, and the selected block catalog.

Preferred simple block mapping:

- `eyebrow` -> `core/paragraph` with a small/supporting style class or plain content when style support is uncertain.
- `headline` -> `core/heading`.
- `body` -> `core/paragraph`.
- `benefits` -> `core/list` with `attributes.items`.
- `coupon` -> supported coupon-oriented block if present in the catalog; otherwise `core/paragraph` containing the code.
- `form` -> `fc/sign-up` with existing nested `inputs.settings.emailOnly`, placeholder, and `button.settings.text` conventions.
- `cta` -> `core/buttons` containing one `core/button`, or a direct `core/button` if current selected catalog lacks a wrapper.
- `countdown` -> supported FooConvert countdown/timer block if present in the catalog; otherwise a text fallback paragraph.
- `image` -> `core/image` when media has an attachment or URL.
- `legal` -> `core/paragraph` with smaller/supporting copy.
- `social_proof`, `product`, `divider` -> selected supported block if available; otherwise safe core fallback (`core/paragraph`, WooCommerce block only when selected).

Layout mapping:

- `single_column`, `stacked`, `flyout_card`: sequential content blocks.
- `bar_inline`: compact sequence: headline/body and CTA, no benefits unless explicitly needed.
- `split`: use `fc/split-layout` only when the selected block catalog includes it and it supports the required child structure; otherwise flatten to sequential blocks.

Block generation must never rely on the model for raw `inner_blocks` when a deterministic builder can assemble them. For complex blocks, the compiler should emit known-good attribute patterns from `DraftNormalizer`/current serializer tests.

### Root Attributes and Style Mapping

Root attributes should remain a FooConvert implementation detail.

- Start with `template_slug` attributes when a valid template is selected.
- Merge deterministic defaults equivalent to `buildRootAttributes()` in `src/admin/ai-popup-builder/serializer-support.js`.
- Apply `style.tokens` only to known safe locations under `root_attributes.styles` or `root_attributes.content.styles`.
- Use `style.preset`, `tone`, and `density` to select an internal preset table. The model should not output arbitrary nested spacing, typography, animation, or shell attributes.
- For `media.intent = generate_background` or available background media, write the same root background structure expected by `DraftImages::inject_background_into_popup_draft()`/`Attachments::root_attributes_have_background()`.

### Media Mapping

- `media.intent = none`: no media block/background.
- `use_existing`: match `attachment_id` or `url` against `existing_media`; insert `core/image` or background when valid.
- `generate_image`: current image ability/tooling may create media; compiler inserts it as `core/image` based on placement.
- `generate_background`: use the existing background generation path where possible and map to `root_attributes.content.styles.background.backgroundImage`.

Image generation should continue to be server-owned. The intent may include a prompt, but generated media should still flow through `ImageGenerator`, `DraftImages`, and `PopupMedia` so media items are imported and sanitized consistently.

## Proposed Implementation Architecture

Add a new compiler layer rather than spreading intent logic across parser, service, and UI code.

Suggested PHP classes:

- `includes/AI/PopupBuilder/Intent/Schema.php`: returns assistant response schema additions and `popup_intent` schema.
- `includes/AI/PopupBuilder/Intent/Normalizer.php`: sanitizes intent fields, repairs aliases, caps arrays/strings, and records non-fatal issues.
- `includes/AI/PopupBuilder/Intent/Compiler.php`: converts normalized intent into current `popup_draft`.
- `includes/AI/PopupBuilder/Intent/Validator.php`: validates required intent semantics before compile and validates compiled draft afterward with `Blueprint\Validator`.

Suggested integration points:

- `ResponseParser::normalize_decoded_popup_response()`: recognize aliases such as `intent`, `popupIntent`, `popup_brief`, and `popupBrief` as `popup_intent`.
- `ResponseParser::validate_decoded_popup_response()`: accept a response that has either a valid `popup_draft`, a valid `popup_intent`, or a clarifying question.
- `DraftNormalizer::sanitize_ai_response()`: preserve optional `popup_intent` in the response and compile it to `popup_draft` when no direct draft is present.
- `Schema::get_assistant_response_schema()`: expose the new top-level key and prefer intent in prompt contracts.
- `PromptFactory::compose_system_instruction()`: tell the model to return `popup_intent` by default and only use `popup_draft` when explicitly requested by a compatibility setting.
- `ChatService::generate_ai_response()`: keep post-response media/background generation working against the compiled `popup_draft`.
- `RestController::prepare_chat_request()`: later accept optional `popup_intent`/`current_intent` from the client for edit turns, but keep `popup_draft` as the compatibility payload initially.

## Migration Strategy

### Phase 1: Dual Accept, Draft Return

- Update schema/parser/normalizer to accept `popup_intent`.
- Compile `popup_intent` to `popup_draft` before returning to the admin app.
- Continue returning `popup_draft` as the primary client render field.
- Persist optional `popup_intent` under saved AI metadata response for debugging/replay, but do not require the editor to consume it.
- Keep the current `popup_draft` path working for old prompts, external LLM variants, tests, and imported metadata.

### Phase 2: Prompt Intent First

- Change assistant response contract text to prefer `popup_intent`.
- Remove most block catalog detail from the final response requirement. Keep block/template tools available to the compiler or for optional context, but stop requiring the model to output block trees.
- Add a setting/feature flag such as `ai_popup_builder_response_mode = intent|draft|dual` for controlled rollout.
- Log whether each turn came from direct draft, compiled intent, or fallback repair.

### Phase 3: Client Awareness

- Add read-only `popup_intent` display to the debug tab in `src/admin/ai-popup-builder/App.js`.
- Optionally send `current_intent` on edit turns once the client can preserve it. Until then, the model can infer edits from `popup_draft` plus chat history.
- Keep preview, details, save, media, and serializer behavior based on compiled `popup_draft`.

### Phase 4: Deprecate Direct Draft Prompting

- Stop asking the model for `content_blocks` by default.
- Keep direct `popup_draft` acceptance for backward compatibility and hand-authored/debug payloads.
- Consider removing direct draft output from default schema only after telemetry shows intent compile success is stable.

## Validation, Sanitization, Repair, and Fallback

### Validation Order

1. Decode response JSON using existing `ResponseParser::decode_json_response_with_metadata()` behavior.
2. Normalize aliases into canonical top-level keys.
3. If `clarifying_question` is non-empty and both draft/intent are empty, accept as a question response.
4. If `popup_intent` is present, sanitize and validate intent.
5. Compile intent into `popup_draft`.
6. Sanitize compiled draft with `DraftNormalizer::sanitize_popup_draft()`.
7. Evaluate compiled draft with `Blueprint\Validator::evaluate_popup_draft()`.
8. If no intent exists but `popup_draft` exists, use the current legacy path.

### Sanitization Rules

- Treat model strings as untrusted. Reuse existing rich/plain text sanitizers where possible.
- Cap string lengths by field type: headline/body/CTA/legal should have different limits.
- Normalize enum aliases: `overlay` -> `popup`, `exit-intent` -> `exit_intent`, `email_capture` -> `form`, etc.
- Drop unknown fields because `additionalProperties: false` is part of the intent contract.
- Cap arrays: benefits to 5, components to 12, rationale/notes to existing limits.
- Validate colors before applying style tokens. Invalid color tokens should be ignored, not repaired into arbitrary values.
- Validate URLs before adding CTA/image attributes. Empty CTA URLs are allowed for form actions.

### Repair Rules

Repair should be semantic and local:

- Missing `schema_version` -> assume `1`.
- Missing `popup_type` -> infer from goal/layout: bar for compact announcements, flyout for lead capture or non-blocking prompts, popup for discount/cart recovery.
- Missing `conversion_components` -> derive from copy and offer.
- Missing CTA label -> derive from offer/action, e.g. `Apply Discount`, `Join the List`, `Continue Checkout`.
- Countdown enabled with no duration/end -> default evergreen duration, e.g. 15 minutes, and record a note.
- Unsupported media placement -> downgrade to `none` or inline image depending on available data.
- Unsupported template preference -> clear `template_slug`.

Repair should not invent mission-critical business facts such as exact discount code, product IDs, legal terms, or expiration date. If those are required for a safe draft and absent, use a clarifying question or a visible placeholder note.

### Fallback Behavior

- If intent validates but compilation yields no content blocks, compile a minimal safe draft: heading, paragraph, CTA or sign-up form based on `copy.cta.action`.
- If a requested component is unsupported, use core text/list/button fallback and add a note.
- If compiled draft validation has warnings but a renderable draft exists, return it with validation warnings.
- If compiled draft is empty or unsafe, ask one clarifying question and return `popup_draft: null`.
- If both `popup_intent` and `popup_draft` exist, prefer compiling intent in intent-first mode; in dual/debug mode, compare both and log drift.

## UI and API Changes

### REST API

- Chat endpoints continue to accept `messages`, `popup_draft`, `brand`, media flags, and settings.
- Chat responses add optional `popup_intent` while preserving `popup_draft`.
- Save metadata should preserve `popup_intent` under the stored `response` object when present.
- Debug response records should include response mode, compiler warnings, repair actions, and whether the final draft was direct or compiled.

### Admin App

- No immediate preview/save changes are required because `App.js` already reads `response.popup_draft`.
- Add debug-tab rendering for `popup_intent`, normalized intent, compiler warnings, and compiled draft when `debugTabAvailable`.
- Consider adding a compact "Intent" details section later for product/QA users, but do not make the UI depend on it for rendering.
- Keep `serializeDraftToMarkup()` unchanged; it should continue to receive compiled `popup_draft`.

### Prompt/Settings UI

- Add an internal setting/feature flag for response mode.
- The Settings tab may expose response mode only for developer/debug users. Default should be intent-first after Phase 2.

## Testing Plan

### PHP Unit/Integration Tests

- `ResponseParser` accepts `popup_intent` without `popup_draft` and rejects empty responses with neither intent/draft nor clarifying question.
- Alias normalization maps `intent`, `popupIntent`, `popup_brief`, and `popupBrief` to `popup_intent`.
- Intent normalizer sanitizes strings, arrays, enums, colors, URLs, and drops unknown fields.
- Compiler maps each `popup_type` to a renderable draft.
- Compiler maps each trigger intent to the expected event/step shape accepted by `DraftNormalizer::sanitize_trigger()`.
- Component mapping tests for headline, body, benefits, form, CTA, coupon, countdown fallback, image, and background.
- Template preference tests: valid matching template applied, mismatched/unknown template cleared.
- Legacy `popup_draft` response tests continue passing.
- Metadata sanitization preserves optional `popup_intent` without breaking existing saved metadata defaults.
- Image/background paths still work after intent compilation because `ChatService` media methods receive a normal draft.

### JS Tests

- `App.js` response handling remains stable when response includes both `popup_intent` and `popup_draft`.
- Debug tab can display intent payloads without affecting draft state.
- Serializer tests remain focused on `popup_draft`; add no serializer dependency on intent.

### Contract Tests

- Golden intent fixtures compile to deterministic draft snapshots.
- Invalid/missing optional fields repair to expected defaults.
- Unsupported components produce safe fallback blocks and notes.
- Round-trip: intent -> draft -> serializer -> parsed/extracted draft remains structurally equivalent enough for editing.

### Manual QA

- Generate discount exit popup, email flyout, shipping bar, product recommendation popup, and checkout error recovery popup.
- Test streaming and non-streaming chat endpoints.
- Test image generation on/off and forced image generation.
- Save generated popup, reload via existing builder load route, and edit again.
- Verify debug logs identify compiled intent mode and repair actions.

## Rollout Plan

1. Land intent schema/compiler behind an off-by-default feature flag.
2. Run fixture tests and local manual prompts while still prompting for `popup_draft`.
3. Enable dual mode for developer/debug builds: accept/compile `popup_intent`, but continue prompting for current draft if needed.
4. Switch prompt contract to intent-first for a small internal rollout.
5. Monitor invalid JSON rate, contract retry rate, compiler fallback rate, validation score/warnings, and save success rate.
6. Expand intent-first default when compiled drafts match or improve current draft quality.
7. Keep direct `popup_draft` compatibility indefinitely unless there is a strong reason to remove it.

## Risks and Tradeoffs

- Compiler complexity moves from model output into FooConvert code. This is intentional, but it needs tests and clear ownership.
- Intent may under-specify advanced custom layouts. The fallback should prefer reliable, conversion-focused drafts over trying to infer elaborate block trees.
- A deterministic compiler can make outputs feel more templated. Mitigate with style presets, template selection, brand tokens, and varied copy structure.
- Existing image tools expect `popup_draft`; compile must happen before media/background generation.
- If the client eventually sends both current draft and current intent, drift between them must be handled carefully.
- Some advanced WooCommerce/content blocks may still require block-level knowledge. Keep an escape hatch where internal compiler code can use the selected block catalog.
- Debugging becomes two-stage: model intent and compiled draft. Logs must show both to avoid opaque failures.

## Open Questions

- Should `popup_intent` be saved permanently in AI metadata, or only debug logs?
- Should edit turns ask the model to patch the previous intent, replace it wholesale, or derive a new intent from `popup_draft` plus chat history?
- Which style presets should be canonical, and should they live in PHP, JS, or shared generated config?
- Should the compiler be PHP-only so REST always returns renderable drafts, or should JS also have a mirror compiler for offline/debug workflows?
- What is the minimum supported countdown block contract? If no stable `fc/countdown` block contract exists, countdown should remain a text fallback until one is documented.
- Should `conversion_components` be ordered by the model or normalized by the compiler based on layout/popup type?
- How much of the existing block catalog ability remains useful once the model no longer emits blocks directly?
- What telemetry/debug counters are acceptable for this plugin context?
