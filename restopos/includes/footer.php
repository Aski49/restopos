    </main><!-- /page-content -->
</div><!-- /main-wrap -->

<script src="../assets/js/app.js"></script>

<!-- ══ POPUP NOTIFICATION TOASTS ══════════════════════════════ -->
<div id="notifStack" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;max-width:340px;pointer-events:none"></div>
<style>
.notif-toast{background:#12151f;border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:14px 16px;box-shadow:0 8px 40px rgba(0,0,0,.6);pointer-events:auto;cursor:pointer;animation:tIn .35s cubic-bezier(.34,1.56,.64,1);display:flex;align-items:flex-start;gap:12px;min-width:270px}
.notif-toast.order{border-left:4px solid #f59e0b}
.notif-toast.reservation{border-left:4px solid #3b82f6}
@keyframes tIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes tOut{from{transform:translateX(0);opacity:1}to{transform:translateX(120%);opacity:0}}
.n-icon{font-size:28px;flex-shrink:0}
.n-body{flex:1;min-width:0}
.n-title{font-weight:700;font-size:13px;color:#f0f4ff;margin-bottom:3px}
.n-msg{font-size:12px;color:#6b7280;line-height:1.5}
.n-act{font-size:11px;font-weight:700;margin-top:5px;color:#f59e0b}
.n-x{width:20px;height:20px;border-radius:50%;border:none;background:rgba(255,255,255,.06);color:#6b7280;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;transition:.15s}
.n-x:hover{background:rgba(239,68,68,.15);color:#ef4444}
</style>

<script>
// ── SIDEBAR ───────────────────────────────────────────────────
function toggleSidebar() {
  var s=document.getElementById('sidebar'),w=document.getElementById('mainWrap'),o=document.getElementById('sidebarOverlay');
  if(window.innerWidth<=768){
    var open=s.classList.contains('mobile-open');
    s.classList.toggle('mobile-open',!open);
    o.classList.toggle('active',!open);
  } else {
    s.classList.toggle('collapsed');
    w.classList.toggle('collapsed');
  }
}
function closeMobileSidebar(){
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('sidebarOverlay').classList.remove('active');
}
document.querySelectorAll('.nav-item').forEach(function(i){
  i.addEventListener('click',function(){if(window.innerWidth<=768)closeMobileSidebar();});
});
window.addEventListener('resize',function(){
  if(window.innerWidth>768)closeMobileSidebar();
});

// ── ONLINE ORDER SIDEBAR BADGE ────────────────────────────────
(function(){
  var badge=document.getElementById('onlineOrderBadge');
  if(!badge)return;
  setInterval(function(){
    var fd=new FormData();fd.append('action','poll');
    fetch('online_orders.php',{method:'POST',body:fd})
      .then(function(r){return r.json();})
      .then(function(d){badge.textContent=d.new_count||0;badge.style.display=d.new_count>0?'':'none';})
      .catch(function(){});
  },30000);
})();

// ── POPUP NOTIFICATION ENGINE ─────────────────────────────────
(function(){
  var shownO=new Set(),shownR=new Set(),firstRun=true;
  try{
    JSON.parse(sessionStorage.getItem('_so')||'[]').forEach(function(i){shownO.add(i);});
    JSON.parse(sessionStorage.getItem('_sr')||'[]').forEach(function(i){shownR.add(i);});
  }catch(e){}

  function save(){
    try{
      sessionStorage.setItem('_so',JSON.stringify([...shownO]));
      sessionStorage.setItem('_sr',JSON.stringify([...shownR]));
    }catch(e){}
  }

  function beep(freq){
    try{
      var ctx=new(window.AudioContext||window.webkitAudioContext)();
      var o=ctx.createOscillator(),g=ctx.createGain();
      o.connect(g);g.connect(ctx.destination);
      o.frequency.value=freq;
      g.gain.setValueAtTime(.12,ctx.currentTime);
      g.gain.exponentialRampToValueAtTime(.001,ctx.currentTime+.5);
      o.start();o.stop(ctx.currentTime+.5);
    }catch(e){}
  }

  function toast(type,icon,title,msg,link){
    var stack=document.getElementById('notifStack');if(!stack)return;
    var t=document.createElement('div');
    t.className='notif-toast '+type;
    t.innerHTML='<div class="n-icon">'+icon+'</div>'
      +'<div class="n-body"><div class="n-title">'+title+'</div><div class="n-msg">'+msg+'</div>'
      +(link?'<div class="n-act">Tap to view →</div>':'')+'</div>'
      +'<button class="n-x" onclick="dismissToast(this)">✕</button>';
    if(link){t.addEventListener('click',function(e){if(!e.target.classList.contains('n-x'))window.location.href=link;});}
    stack.appendChild(t);
    beep(type==='order'?900:660);
    setTimeout(function(){dismissToast(t.querySelector('.n-x'));},9000);
  }

  window.dismissToast=function(btn){
    var t=btn.closest?btn.closest('.notif-toast'):btn.parentElement;
    if(!t)return;
    t.style.animation='tOut .3s ease forwards';
    setTimeout(function(){if(t.parentNode)t.parentNode.removeChild(t);},300);
  };

  // Detect base path for module fetches
  var _base=(function(){var s=window.location.pathname;var i=s.lastIndexOf('/modules/');return i>=0?s.substring(0,i)+'/modules/':s.substring(0,s.lastIndexOf('/')+1);})();

  function safeJson(r){return r.text().then(function(t){try{return JSON.parse(t);}catch(e){return {};}});}

  function poll(){
    // Poll online orders
    var fd=new FormData();fd.append('action','poll_new');
    fetch(_base+'online_orders.php',{method:'POST',body:fd})
      .then(safeJson)
      .then(function(d){
        if(d.new_orders){d.new_orders.forEach(function(o){
          if(!shownO.has(o.id)){shownO.add(o.id);save();
            if(!firstRun)toast('order','🛒','New Online Order!',o.customer_name+' · '+o.type_label+' · Rs.'+o.total,_base+'online_orders.php');
          }
        });}
      }).catch(function(){});

    // Poll reservations
    var fd2=new FormData();fd2.append('action','poll_new');
    fetch(_base+'reservations.php',{method:'POST',body:fd2})
      .then(safeJson)
      .then(function(d){
        if(d.new_reservations){d.new_reservations.forEach(function(r){
          if(!shownR.has(r.id)){shownR.add(r.id);save();
            if(!firstRun)toast('reservation','📅','New Reservation!',r.customer_name+' · '+r.pax+' guests · '+r.date+' '+r.time,_base+'reservations.php');
          }
        });}
      }).catch(function(){});

    firstRun=false;
  }

  setTimeout(poll,1500);
  setInterval(poll,15000);
})();
</script>
</body>
</html>
