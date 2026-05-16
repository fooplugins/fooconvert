<?php

defined( 'ABSPATH' ) || exit;

return array(
    'principles'      => array(
        'Lead with one clear benefit in the first line.',
        'Keep to a single primary CTA unless the user explicitly asks for alternatives.',
        'Use urgency carefully: real scarcity, deadlines, or fast outcomes outperform vague hype.',
        'Match friction to intent: low-friction offers for cold visitors, richer forms for high-intent visitors.',
        'Support the headline with proof, specificity, or a concise value stack.',
        'Mobile legibility matters: the headline must communicate the offer without needing to scroll or tap "read more".',
    ),
    'avoid'           => array(
        'Empty intensifiers like amazing, incredible, ultimate, game-changing, or revolutionary.',
        'Vague benefits like "level up", "transform your business", or "unlock your potential".',
        'Question headlines that delay the value ("Tired of slow sites?") instead of leading with the offer.',
        'Fake urgency ("Only 2 left!", "Ends soon!") without real scarcity or a real deadline.',
        'Stacking exclamation marks, ALL CAPS shouting, or emoji-as-bullets.',
        'Generic newsletter asks like "Subscribe for updates" with no described reward.',
    ),
    'popup_types'     => array(
        'popup'  => array(
            'best_for'    => 'High-focus offers, lead capture, launch promos, and exit-intent campaigns.',
            'watchouts'   => 'Avoid too many sections or competing CTAs.',
            'length'      => 'Up to roughly 60 words total across headline, supporting line, and CTA. Use the extra room for proof or a short value stack, not filler.',
            'exit_intent' => 'When the trigger is exit-intent, acknowledge the leave ("Before you go", "One last thing") and consider softening the ask - email instead of a trial, a discount instead of a full signup.',
        ),
        'flyout' => array(
            'best_for'  => 'Mid-journey nudges, content upgrades, sticky promos, and lower interruption offers.',
            'watchouts' => 'Keep the width tight and copy concise.',
            'length'    => 'Roughly 25 to 40 words total. One headline, one supporting line, one CTA.',
        ),
        'bar'    => array(
            'best_for'  => 'Announcements, coupon reveals, shipping thresholds, and lightweight newsletter asks.',
            'watchouts' => 'Bars work best with one-line value and one action.',
            'length'    => 'Roughly 6 to 10 words total, including the CTA. If it does not fit on one line on mobile, it is too long.',
        ),
    ),
    'copy_tactics'    => array(
        'Favor concrete outcomes over generic adjectives.',
        'Use CTA verbs that imply immediacy: claim, unlock, get, start, reserve.',
        'If asking for email, describe what arrives and when. "Get the 5-page WordPress speed checklist in your inbox in 60 seconds" beats "Join our newsletter." Name the deliverable, the format, and the timing.',
        'Use supportive microcopy to reduce risk: cancel anytime, no spam, ships today, limited batch.',
    ),
    'proof_hierarchy' => array(
        'guidance' => 'When supporting a headline with proof, prefer stronger forms over weaker ones. Do not default to generic claims when specifics are available in context.',
        'ranked'   => array(
            '1. Specific numbers ("Used on 47,000 WordPress sites", "Cuts load time by 1.8s on average").',
            '2. Named customers or recognizable logos ("Used by Shopify, Automattic, and 200+ agencies").',
            '3. Testimonials with attribution (name, role, company).',
            '4. Generic claims ("Trusted by thousands") - only when nothing stronger is available.',
        ),
    ),
    'examples'        => array(
        'newsletter_ask' => array(
            'before' => 'Subscribe for updates',
            'after'  => 'Get one WordPress tip every Tuesday. No fluff, unsubscribe anytime.',
        ),
        'lead_magnet'    => array(
            'before' => 'Download our free guide!',
            'after'  => 'Get the 12-point site speed checklist (PDF, 5 min read).',
        ),
        'discount_bar'   => array(
            'before' => 'Huge savings inside - don\'t miss out!',
            'after'  => 'Save 20% today - code SPEED20 at checkout.',
        ),
    ),
);
