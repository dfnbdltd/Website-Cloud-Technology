<?php
/**
 * Append the standalone coming-soon page styles to public/css/coming-soon.css.
 * The file already carries the modal styles; the page block below is scoped
 * under .cs-page so the two can never leak into each other.
 */
$path = __DIR__ . '/../public/css/coming-soon.css';
$raw = file_get_contents($path);

if (strpos($raw, '.cs-page') !== false) {
    echo "page styles already present\n";
    exit(0);
}

$eol = (strpos($raw, "\r\n") !== false) ? "\r\n" : "\n";

$css = <<<'CSS'

/* ===========================================================================
   Standalone "coming soon" page (resources/views/coming-soon.blade.php).
   Scoped under .cs-page so it shares nothing with the modal above.
   The accent colour arrives as --cs-accent from config/coming_soon.php.
   =========================================================================== */
.cs-page{--cs-accent:#2563eb;display:flex;flex-direction:column;min-height:100vh;margin:0;background:#f6f9fd;color:#17365e;font:15px/1.6 'DM Sans',sans-serif}
.cs-page *,.cs-page *:before,.cs-page *:after{box-sizing:border-box}
.cs-page a{text-decoration:none}

/* The card sits in the free space between navbar and footer, so the page
   fills the viewport at any height instead of hugging the top. */
.cs-main{flex:1 1 auto;display:grid;place-items:center;padding:clamp(32px,6vw,76px) 20px}

.cs-card{position:relative;width:min(560px,100%);padding:clamp(32px,5vw,52px) clamp(24px,4.5vw,48px) clamp(26px,4vw,38px);overflow:hidden;border:1px solid #e3edf8;border-radius:26px;background:linear-gradient(160deg,#ffffff,#f4f9ff);box-shadow:0 26px 70px #1a4a7c14,0 2px 6px #1a4a7c0a;text-align:center}

/* Soft accent halo behind the badge. */
.cs-glow{position:absolute;top:-120px;left:50%;width:280px;height:280px;margin-left:-140px;border-radius:50%;background:color-mix(in srgb,var(--cs-accent) 16%,transparent);filter:blur(12px);pointer-events:none}

/* Brand mark badge. */
.cs-badge{position:relative;display:grid;place-items:center;width:74px;height:74px;margin:0 auto 22px;border:1px solid color-mix(in srgb,var(--cs-accent) 24%,#ffffff);border-radius:22px;background:linear-gradient(150deg,#fbfdff,#eaf3ff);box-shadow:inset 0 1px 0 #fff,0 12px 26px color-mix(in srgb,var(--cs-accent) 16%,transparent)}
.cs-badge .ct-logo{display:grid;place-items:center;width:100%;height:100%}
.cs-badge .ct-logo-img{width:48px;height:48px;object-fit:contain}

.cs-eyebrow{position:relative;display:inline-block;margin:0;color:var(--cs-accent);font-size:11px;font-weight:800;letter-spacing:1.9px;text-transform:uppercase}
.cs-title{position:relative;margin:12px 0;color:#102a4c;font:800 clamp(24px,4.2vw,32px)/1.22 'Manrope',sans-serif;letter-spacing:-.8px}
.cs-message{position:relative;max-width:400px;margin:0 auto;color:#6b829c;font-size:15px;line-height:1.7}

/* Three reassurance points, one per row, each with an accent tick. */
.cs-points{position:relative;display:grid;gap:10px;width:fit-content;max-width:100%;margin:24px auto 0;padding:0;list-style:none;text-align:left}
.cs-points li{position:relative;padding-left:27px;color:#4f6a88;font-size:13.5px;line-height:1.55}
.cs-points li:before{position:absolute;top:2px;left:0;display:grid;place-items:center;width:17px;height:17px;border-radius:50%;background:color-mix(in srgb,var(--cs-accent) 15%,#ffffff);color:var(--cs-accent);content:"\2713";font-size:10px;font-weight:800}

.cs-actions{position:relative;display:flex;flex-wrap:wrap;justify-content:center;gap:11px;margin-top:30px}
.cs-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;padding:0 22px;border-radius:12px;font-size:14px;font-weight:700;transition:background .18s ease,box-shadow .18s ease,color .18s ease,transform .18s ease}

/* Primary: the design's navy button. */
.cs-btn-primary{border:1px solid #17365e;background:#17365e;color:#fff;box-shadow:0 10px 22px #17365e26}
.cs-btn-primary:hover{background:#1f4d80;border-color:#1f4d80;box-shadow:0 13px 26px #17365e30;transform:translateY(-1px)}

/* Secondary: quiet outline so "Go back" always reads as the main action. */
.cs-btn-ghost{border:1px solid #d5e3f1;background:#fff;color:#2f5680}
.cs-btn-ghost:hover{border-color:color-mix(in srgb,var(--cs-accent) 45%,#d5e3f1);background:#f7fbff;color:var(--cs-accent)}

.cs-btn:focus-visible{outline:3px solid color-mix(in srgb,var(--cs-accent) 38%,transparent);outline-offset:3px}

.cs-note{position:relative;margin:24px 0 0;padding-top:18px;border-top:1px solid #eaf1f9;color:#8ea2b8;font-size:12.5px}
.cs-note a{color:var(--cs-accent);font-weight:700}
.cs-note a:hover{text-decoration:underline}

/* Footer: same dark bar as the rest of the public site. */
.cs-page .site-footer{background:#0f172a;padding:30px 0;color:#94a3b8;font-size:13px}
.cs-page .site-footer-inner{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;max-width:1240px;margin:0 auto;padding:0 32px}
.cs-page .site-footer .ct-logo-img{height:50px}
.cs-page .site-footer span{color:#94a3b8}
.cs-page .site-footer a{color:#c7d8ea}
.cs-page .site-footer a:hover{color:#fff}

@media(max-width:560px){
.cs-page .site-footer-inner{justify-content:center;padding:0 20px;text-align:center;gap:12px}
.cs-actions{flex-direction:column;align-items:stretch}
.cs-btn{width:100%}
.cs-card{border-radius:20px}
}

/* Keep the page calm for visitors who ask for less motion. */
@media(prefers-reduced-motion:reduce){
.cs-btn{transition:none}
}
CSS;

$raw = rtrim($raw) . $eol . $css . $eol;
file_put_contents($path, $raw);
echo "page styles appended\n";
