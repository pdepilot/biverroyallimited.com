<?php
/**
 * Floating WhatsApp live-chat launcher (bottom-left on every frontend page).
 * Self-contained: markup + scoped CSS 3D animation. No external dependencies.
 */
declare(strict_types=1);

$waNumber  = '2348142523251';
$waDisplay = '+234 814 252 3251';
$waText    = rawurlencode('Hello Biver Royalty Homes, I would like to make an enquiry.');
$waHref    = 'https://wa.me/' . $waNumber . '?text=' . $waText;
?>
<div class="wa-widget">
<div class="wa-welcome" id="waWelcome" role="dialog" aria-label="Welcome message from Biver Royalty Homes">
  <div class="wa-welcome__head">
    <span class="wa-welcome__ava">
      <svg viewBox="0 0 24 24"><path fill="#fff" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.86 9.86 0 0 0 12.04 2zm5.8 14.06c-.24.68-1.42 1.31-1.96 1.39-.5.07-1.13.1-1.83-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.27-.29.58-.36.78-.36l.56.01c.18.01.42-.07.66.5.24.58.83 2.01.9 2.16.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.3.38-.43.5-.14.14-.29.3-.12.58.16.29.72 1.19 1.55 1.92 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.69-.81.88-1.09.18-.28.37-.23.62-.14.25.09 1.62.76 1.9.9.28.14.46.21.53.33.07.12.07.69-.17 1.37z"/></svg>
    </span>
    <span>
      <span class="wa-welcome__name">Biver Royalty Homes</span>
      <span class="wa-welcome__status">Online now</span>
    </span>
    <button type="button" class="wa-welcome__close" id="waWelcomeClose" aria-label="Close welcome message">&times;</button>
  </div>
  <div class="wa-welcome__body">
    <div class="wa-welcome__msg">
      <span class="wa-welcome__wave">👋</span> Welcome to <b>Biver Royalty Homes</b>! Looking for your dream home, or want to list a property? We're here to help — chat with us right now.
      <span class="wa-welcome__time">Just now</span>
    </div>
    <a class="wa-welcome__cta" href="<?= htmlspecialchars($waHref, ENT_QUOTES) ?>" target="_blank" rel="noopener noreferrer">
      <svg viewBox="0 0 24 24"><path fill="#fff" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.86 9.86 0 0 0 12.04 2zm5.8 14.06c-.24.68-1.42 1.31-1.96 1.39-.5.07-1.13.1-1.83-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.27-.29.58-.36.78-.36l.56.01c.18.01.42-.07.66.5.24.58.83 2.01.9 2.16.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.3.38-.43.5-.14.14-.29.3-.12.58.16.29.72 1.19 1.55 1.92 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.69-.81.88-1.09.18-.28.37-.23.62-.14.25.09 1.62.76 1.9.9.28.14.46.21.53.33.07.12.07.69-.17 1.37z"/></svg>
      Start Chat
    </a>
  </div>
</div>
<a class="wa-fab" href="<?= htmlspecialchars($waHref, ENT_QUOTES) ?>"
   target="_blank" rel="noopener noreferrer"
   aria-label="Chat with Biver Royalty Homes on WhatsApp (<?= htmlspecialchars($waDisplay, ENT_QUOTES) ?>)">
  <span class="wa-bob">
    <span class="wa-shadow" aria-hidden="true"></span>
    <span class="wa-scene">
      <span class="wa-orbit" aria-hidden="true"><i class="wa-spark"></i></span>
      <span class="wa-ring" aria-hidden="true"></span>
      <span class="wa-ring wa-ring--2" aria-hidden="true"></span>
      <span class="wa-ring wa-ring--3" aria-hidden="true"></span>
      <span class="wa-coin" aria-hidden="true">
<?php for ($i = -10; $i <= 10; $i++): ?>
        <span class="wa-layer" style="transform:translateZ(<?= $i ?>px)"></span>
<?php endfor; ?>
        <span class="wa-face wa-face--back">
          <svg viewBox="0 0 24 24"><path fill="#fff" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.86 9.86 0 0 0 12.04 2zm5.8 14.06c-.24.68-1.42 1.31-1.96 1.39-.5.07-1.13.1-1.83-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.27-.29.58-.36.78-.36l.56.01c.18.01.42-.07.66.5.24.58.83 2.01.9 2.16.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.3.38-.43.5-.14.14-.29.3-.12.58.16.29.72 1.19 1.55 1.92 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.69-.81.88-1.09.18-.28.37-.23.62-.14.25.09 1.62.76 1.9.9.28.14.46.21.53.33.07.12.07.69-.17 1.37z"/></svg>
        </span>
        <span class="wa-face wa-face--front">
          <svg viewBox="0 0 24 24"><path fill="#fff" d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38c1.45.79 3.08 1.21 4.79 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.86 9.86 0 0 0 12.04 2zm5.8 14.06c-.24.68-1.42 1.31-1.96 1.39-.5.07-1.13.1-1.83-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.19-1.18-1.57-1.18-3s.75-2.13 1.02-2.42c.27-.29.58-.36.78-.36l.56.01c.18.01.42-.07.66.5.24.58.83 2.01.9 2.16.07.14.12.31.02.5-.09.19-.14.31-.28.48-.14.17-.3.38-.43.5-.14.14-.29.3-.12.58.16.29.72 1.19 1.55 1.92 1.06.95 1.96 1.24 2.24 1.38.28.14.44.12.6-.07.16-.19.69-.81.88-1.09.18-.28.37-.23.62-.14.25.09 1.62.76 1.9.9.28.14.46.21.53.33.07.12.07.69-.17 1.37z"/></svg>
        </span>
      </span>
      <span class="wa-dot" aria-hidden="true"></span>
    </span>
  </span>
  <span class="wa-label" aria-hidden="true">Chat with us<small>WhatsApp &bull; replies instantly</small></span>
</a>
</div>

<style>
  .wa-widget{
    position:fixed; left:22px; bottom:28px; z-index:9998;
    -webkit-tap-highlight-color:transparent;
    width:66px; height:66px; pointer-events:none;
  }
  .wa-widget *{box-sizing:border-box;}
  .wa-fab{
    position:relative; display:block; pointer-events:auto;
    width:66px; height:66px; text-decoration:none;
    overflow:visible;
  }
  .wa-bob{
    position:relative; display:block; width:66px; height:66px;
    animation:waBob 3.4s ease-in-out infinite;
    transform-origin:50% 100%;
  }
  @keyframes waBob{0%,100%{transform:translateY(0) rotate(-2deg);}50%{transform:translateY(-12px) rotate(2deg);}}

  /* Ground shadow kept OUTSIDE the 3D chain (no filter on the perspective host,
     which otherwise makes GPU Chrome render the 3D coin invisible). */
  .wa-shadow{
    position:absolute; left:50%; bottom:-7px; width:46px; height:12px;
    transform:translateX(-50%); border-radius:50%;
    background:rgba(9,120,61,.5); filter:blur(6px); z-index:0;
  }
  .wa-scene{
    position:relative; z-index:1; width:66px; height:66px;
    perspective:900px; perspective-origin:50% 45%;
    transition:transform .4s cubic-bezier(.2,.9,.25,1.4);
  }
  .wa-fab:hover .wa-scene{transform:scale(1.09);}

  /* --- The rotating, extruded 3D coin (wobble keeps a face toward viewer,
         so it shows rich depth on the sides but never a thin edge-on sliver) --- */
  .wa-coin{
    position:absolute; inset:0; transform-style:preserve-3d;
    transform:rotateY(-32deg) rotateX(7deg);
    animation:waSpin 4.6s ease-in-out infinite;
  }
  @keyframes waSpin{
    0%,100%{transform:rotateY(-32deg) rotateX(7deg);}
    50%{transform:rotateY(32deg) rotateX(-7deg);}
  }
  .wa-scene:hover .wa-coin{animation-duration:2.2s;}

  .wa-layer{
    position:absolute; inset:0; border-radius:50%;
    background:linear-gradient(#0e8f50,#0a6f3d);
  }
  .wa-face{
    position:absolute; inset:0; border-radius:50%;
    display:grid; place-items:center;
    background:radial-gradient(circle at 33% 28%,#5cf08a 0%,#25d366 42%,#12a150 74%,#0a7d3e 100%);
    box-shadow:inset 0 3px 9px rgba(255,255,255,.55), inset 0 -8px 16px rgba(0,0,0,.30);
    backface-visibility:hidden;
  }
  .wa-face--front{transform:translateZ(11px);}
  .wa-face--back{transform:rotateY(180deg) translateZ(11px);}
  .wa-face svg{width:58%; height:58%;}

  /* --- Orbiting spark (tilted 3D ring path) --- */
  .wa-orbit{
    position:absolute; inset:-7px; transform-style:preserve-3d;
    transform:rotateX(68deg); animation:waOrbit 3.6s linear infinite;
    pointer-events:none;
  }
  @keyframes waOrbit{to{transform:rotateX(68deg) rotateZ(360deg);}}
  .wa-spark{
    position:absolute; left:50%; top:-5px; width:9px; height:9px; margin-left:-4.5px;
    border-radius:50%; background:radial-gradient(#fff,#8dffb9 60%,rgba(141,255,185,0));
    box-shadow:0 0 12px 3px rgba(141,255,185,.9);
  }

  /* --- Pulse rings --- */
  .wa-ring{
    position:absolute; left:50%; top:50%; width:66px; height:66px; margin:-33px 0 0 -33px;
    border-radius:50%; border:2px solid rgba(37,211,102,.55);
    animation:waPulse 2.8s ease-out infinite; pointer-events:none;
  }
  .wa-ring--2{animation-delay:.95s;}
  .wa-ring--3{animation-delay:1.9s;}
  @keyframes waPulse{0%{transform:scale(.62);opacity:.85;}100%{transform:scale(2.2);opacity:0;}}

  /* --- Online dot --- */
  .wa-dot{
    position:absolute; right:-1px; top:-1px; width:16px; height:16px; border-radius:50%;
    background:#37e06a; border:3px solid #fff; z-index:6;
    box-shadow:0 0 0 2px rgba(55,224,106,.35);
    animation:waBlink 1.6s ease-in-out infinite;
  }
  @keyframes waBlink{0%,100%{box-shadow:0 0 0 2px rgba(55,224,106,.35);}50%{box-shadow:0 0 0 7px rgba(55,224,106,0);}}

  /* --- Hover label --- */
  .wa-label{
    position:absolute; left:80px; top:33px; transform:translateY(-50%) translateX(-14px) scale(.92);
    background:#fff; color:#0a2540; font:600 13px/1.25 -apple-system,Segoe UI,Roboto,system-ui,sans-serif;
    padding:9px 15px; border-radius:13px; white-space:nowrap;
    box-shadow:0 14px 34px rgba(0,0,0,.20); opacity:0; pointer-events:none;
    transition:opacity .3s ease, transform .35s cubic-bezier(.2,.8,.2,1.3);
  }
  .wa-label small{display:block; color:#12a150; font-weight:700; font-size:11px; margin-top:1px;}
  .wa-label::before{
    content:""; position:absolute; left:-5px; top:50%; width:12px; height:12px;
    background:#fff; transform:translateY(-50%) rotate(45deg); border-radius:2px;
  }
  .wa-fab:hover .wa-label,.wa-fab:focus-visible .wa-label{
    opacity:1; transform:translateY(-50%) translateX(0) scale(1);
  }

  /* --- Auto welcome popup --- */
  .wa-welcome{
    position:fixed; left:14px; bottom:100px; width:300px; max-width:calc(100% - 28px);
    background:#fff; border-radius:18px; overflow:hidden;
    box-shadow:0 24px 60px rgba(0,0,0,.28);
    transform-origin:left bottom; transform:translateY(16px) scale(.85);
    opacity:0; visibility:hidden; pointer-events:none;
    transition:opacity .35s ease, transform .5s cubic-bezier(.2,.85,.25,1.35), visibility .35s;
    z-index:9999;
  }
  .wa-welcome.is-open{opacity:1; visibility:visible; pointer-events:auto; transform:translateY(0) scale(1);}
  .wa-welcome__head{
    display:flex; align-items:center; gap:11px;
    background:linear-gradient(135deg,#0f9c4d,#25d366); padding:13px 14px; color:#fff;
  }
  .wa-welcome__ava{
    width:40px; height:40px; border-radius:50%; flex:none;
    background:rgba(255,255,255,.22); display:grid; place-items:center;
  }
  .wa-welcome__ava svg{width:24px; height:24px;}
  .wa-welcome__name{display:block; font:700 14px/1.15 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;}
  .wa-welcome__status{display:flex; align-items:center; gap:6px; margin-top:2px; font:500 11px system-ui; opacity:.95;}
  .wa-welcome__status::before{
    content:""; width:7px; height:7px; border-radius:50%; background:#d6ffe6;
    animation:waBlink 1.6s ease-in-out infinite;
  }
  .wa-welcome__close{
    margin-left:auto; background:transparent; border:0; color:#fff; cursor:pointer;
    font-size:24px; line-height:1; opacity:.85; padding:0 2px; transition:opacity .2s;
  }
  .wa-welcome__close:hover{opacity:1;}
  .wa-welcome__body{
    padding:16px 14px 14px;
    background:#ece5dd;
    background-image:radial-gradient(rgba(0,0,0,.035) 1px,transparent 1px);
    background-size:14px 14px;
  }
  .wa-welcome__msg{
    position:relative; background:#fff; color:#1f2d3d;
    border-radius:4px 16px 16px 16px; padding:11px 13px 22px;
    font:500 13px/1.55 system-ui,-apple-system,Segoe UI,Roboto,sans-serif;
    box-shadow:0 2px 8px rgba(0,0,0,.10);
    animation:waMsgIn .45s both .15s;
  }
  @keyframes waMsgIn{from{opacity:0; transform:translateY(8px) scale(.96);}to{opacity:1; transform:none;}}
  .wa-welcome__wave{display:inline-block; animation:waWave 2s ease-in-out infinite; transform-origin:70% 80%;}
  @keyframes waWave{0%,60%,100%{transform:rotate(0);}10%{transform:rotate(16deg);}20%{transform:rotate(-8deg);}30%{transform:rotate(16deg);}40%{transform:rotate(-4deg);}50%{transform:rotate(10deg);}}
  .wa-welcome__time{position:absolute; right:11px; bottom:6px; font-size:10px; color:#9aa5ad;}
  .wa-welcome__cta{
    display:flex; align-items:center; justify-content:center; gap:8px;
    margin-top:12px; padding:12px; border-radius:13px; text-decoration:none;
    background:linear-gradient(135deg,#12a150,#25d366); color:#fff;
    font:700 13px system-ui; box-shadow:0 10px 20px rgba(37,211,102,.4);
    transition:transform .2s ease, box-shadow .2s ease;
  }
  .wa-welcome__cta svg{width:18px; height:18px;}
  .wa-welcome__cta:hover{transform:translateY(-2px); box-shadow:0 14px 26px rgba(37,211,102,.5);}

  @media (max-width:600px){
    .wa-widget{left:14px; bottom:20px;}
    .wa-fab{width:56px; height:56px;}
    .wa-bob,.wa-scene{width:56px; height:56px;}
    .wa-ring{width:56px; height:56px; margin:-28px 0 0 -28px;}
    .wa-label{display:none;}
    .wa-welcome{left:12px; bottom:86px; max-width:calc(100% - 24px);}
  }
  @media (prefers-reduced-motion:reduce){
    .wa-bob,.wa-coin,.wa-orbit,.wa-ring,.wa-dot,.wa-welcome__wave{animation:none !important;}
  }
</style>

<script>
(function(){
  var pop = document.getElementById('waWelcome');
  if (!pop) return;
  var closeBtn = document.getElementById('waWelcomeClose');
  var KEY = 'brhWaWelcomeSeen';

  function dismiss(remember){
    pop.classList.remove('is-open');
    if (remember){ try { sessionStorage.setItem(KEY, '1'); } catch (e) {} }
  }

  if (closeBtn){
    closeBtn.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      dismiss(true);
    });
  }

  var seen = false;
  try { seen = sessionStorage.getItem(KEY) === '1'; } catch (e) {}

  if (!seen){
    setTimeout(function(){ pop.classList.add('is-open'); }, 1600);
    // Auto-collapse after a while so it never blocks the page.
    setTimeout(function(){ dismiss(false); }, 14000);
  }
})();
</script>
