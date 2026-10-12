@php($globalTheme = \App\Models\GlobalThemeSettings::currentValues())

<style id="global-theme-colors">
    html:root {
        --accent: {{ $globalTheme['accent'] }};
        --accent-warm: color-mix(in srgb, {{ $globalTheme['accent'] }} 84%, {{ $globalTheme['light_surface'] }});
        --accent-bright: color-mix(in srgb, {{ $globalTheme['accent'] }} 82%, #000000);
        --bg-primary: {{ $globalTheme['light_background'] }};
        --bg-secondary: color-mix(in srgb, {{ $globalTheme['light_background'] }} 65%, {{ $globalTheme['light_surface'] }});
        --bg-elevated: {{ $globalTheme['light_surface'] }};
        --bg-inner: color-mix(in srgb, {{ $globalTheme['light_background'] }} 60%, {{ $globalTheme['light_surface'] }});
        --text-primary: {{ $globalTheme['light_text'] }};
        --text-muted: color-mix(in srgb, {{ $globalTheme['light_text'] }} 62%, {{ $globalTheme['light_background'] }});
        --forest: {{ $globalTheme['light_text'] }};
        --border-soft: color-mix(in srgb, {{ $globalTheme['light_text'] }} 12%, transparent);
        --neon-brown: {{ $globalTheme['accent'] }};
        --neon-brown-light: var(--accent-warm);
        --neon-brown-dark: var(--accent-bright);
        --neon-glow: color-mix(in srgb, var(--accent) 30%, transparent);
        --neon-brown-glow: color-mix(in srgb, var(--accent) 25%, transparent);
        --neon-brown-glow-strong: color-mix(in srgb, var(--accent) 40%, transparent);
    }

    html.dark {
        --accent: {{ $globalTheme['accent'] }};
        --accent-warm: color-mix(in srgb, {{ $globalTheme['accent'] }} 82%, {{ $globalTheme['dark_text'] }});
        --accent-bright: color-mix(in srgb, {{ $globalTheme['accent'] }} 85%, #000000);
        --bg-primary: {{ $globalTheme['dark_background'] }};
        --bg-secondary: color-mix(in srgb, {{ $globalTheme['dark_background'] }} 50%, {{ $globalTheme['dark_surface'] }});
        --bg-elevated: {{ $globalTheme['dark_surface'] }};
        --bg-inner: color-mix(in srgb, {{ $globalTheme['dark_background'] }} 60%, {{ $globalTheme['dark_surface'] }});
        --text-primary: {{ $globalTheme['dark_text'] }};
        --text-muted: color-mix(in srgb, {{ $globalTheme['dark_text'] }} 62%, {{ $globalTheme['dark_background'] }});
        --forest: {{ $globalTheme['dark_text'] }};
        --border-soft: color-mix(in srgb, {{ $globalTheme['dark_text'] }} 12%, transparent);
    }

    /* Older page fragments define their own copies of the shared palette.
       Reassert theme tokens on descendants so super-admin choices reach them too. */
    html:root,
    html:root * {
        --accent: {{ $globalTheme['accent'] }} !important;
        --accent-warm: color-mix(in srgb, {{ $globalTheme['accent'] }} 84%, {{ $globalTheme['light_surface'] }}) !important;
        --accent-bright: color-mix(in srgb, {{ $globalTheme['accent'] }} 82%, #000000) !important;
        --bg-primary: {{ $globalTheme['light_background'] }} !important;
        --bg-secondary: color-mix(in srgb, {{ $globalTheme['light_background'] }} 65%, {{ $globalTheme['light_surface'] }}) !important;
        --bg-elevated: {{ $globalTheme['light_surface'] }} !important;
        --bg-inner: color-mix(in srgb, {{ $globalTheme['light_background'] }} 60%, {{ $globalTheme['light_surface'] }}) !important;
        --text-primary: {{ $globalTheme['light_text'] }} !important;
        --text-muted: color-mix(in srgb, {{ $globalTheme['light_text'] }} 62%, {{ $globalTheme['light_background'] }}) !important;
        --forest: {{ $globalTheme['light_text'] }} !important;
        --border-soft: color-mix(in srgb, {{ $globalTheme['light_text'] }} 12%, transparent) !important;
        --neon-brown: {{ $globalTheme['accent'] }} !important;
        --neon-brown-light: color-mix(in srgb, {{ $globalTheme['accent'] }} 84%, {{ $globalTheme['light_surface'] }}) !important;
        --neon-brown-dark: color-mix(in srgb, {{ $globalTheme['accent'] }} 82%, #000000) !important;
        --neon-glow: color-mix(in srgb, {{ $globalTheme['accent'] }} 30%, transparent) !important;
        --neon-brown-glow: color-mix(in srgb, {{ $globalTheme['accent'] }} 25%, transparent) !important;
        --neon-brown-glow-strong: color-mix(in srgb, {{ $globalTheme['accent'] }} 40%, transparent) !important;
    }

    html.dark,
    html.dark * {
        --accent: {{ $globalTheme['accent'] }} !important;
        --accent-warm: color-mix(in srgb, {{ $globalTheme['accent'] }} 82%, {{ $globalTheme['dark_text'] }}) !important;
        --accent-bright: color-mix(in srgb, {{ $globalTheme['accent'] }} 85%, #000000) !important;
        --bg-primary: {{ $globalTheme['dark_background'] }} !important;
        --bg-secondary: color-mix(in srgb, {{ $globalTheme['dark_background'] }} 50%, {{ $globalTheme['dark_surface'] }}) !important;
        --bg-elevated: {{ $globalTheme['dark_surface'] }} !important;
        --bg-inner: color-mix(in srgb, {{ $globalTheme['dark_background'] }} 60%, {{ $globalTheme['dark_surface'] }}) !important;
        --text-primary: {{ $globalTheme['dark_text'] }} !important;
        --text-muted: color-mix(in srgb, {{ $globalTheme['dark_text'] }} 62%, {{ $globalTheme['dark_background'] }}) !important;
        --forest: {{ $globalTheme['dark_text'] }} !important;
        --border-soft: color-mix(in srgb, {{ $globalTheme['dark_text'] }} 12%, transparent) !important;
        --neon-brown: {{ $globalTheme['accent'] }} !important;
        --neon-brown-light: color-mix(in srgb, {{ $globalTheme['accent'] }} 82%, {{ $globalTheme['dark_text'] }}) !important;
        --neon-brown-dark: color-mix(in srgb, {{ $globalTheme['accent'] }} 85%, #000000) !important;
        --neon-glow: color-mix(in srgb, {{ $globalTheme['accent'] }} 30%, transparent) !important;
        --neon-brown-glow: color-mix(in srgb, {{ $globalTheme['accent'] }} 25%, transparent) !important;
        --neon-brown-glow-strong: color-mix(in srgb, {{ $globalTheme['accent'] }} 40%, transparent) !important;
    }

    html:root,
    html.dark {
        background-color: var(--bg-primary) !important;
        color: var(--text-primary);
    }

    html body {
        background-color: var(--bg-primary) !important;
        color: var(--text-primary) !important;
    }
</style>
