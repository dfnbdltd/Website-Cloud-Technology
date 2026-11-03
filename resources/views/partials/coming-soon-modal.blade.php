@php($comingSoonCssVersion = @md5_file(public_path('css/coming-soon.css')))
<link rel="stylesheet" href="{{ asset('css/coming-soon.css') }}@if($comingSoonCssVersion)?v={{ $comingSoonCssVersion }}@endif">
<div class="coming-soon-modal" id="comingSoonModal" role="dialog" aria-modal="true" aria-labelledby="comingSoonTitle" aria-describedby="comingSoonMessage" hidden>
    <div class="coming-soon-dialog" role="document">
        <button class="coming-soon-close" type="button" aria-label="Close dialog" data-coming-soon-close>×</button>
        <div class="coming-soon-icon" aria-hidden="true">
            @include('partials.logo', ['gid' => 'coming-soon'])
        </div>
        <span class="coming-soon-eyebrow">Coming soon</span>
        <h2 id="comingSoonTitle">A better experience is on the way</h2>
        <p id="comingSoonMessage">We are carefully preparing this service for you. It will be available soon.</p>
        <button class="coming-soon-action" type="button" data-coming-soon-close>Got it</button>
    </div>
</div>
<script>
    (function(){
        var modals=document.querySelectorAll('.coming-soon-modal');
        var modal=modals[0];
        if(!modal){return;}
        for(var i=1;i<modals.length;i++){modals[i].remove();}
        if(modal.getAttribute('data-coming-soon-initialized')==='true'){return;}
        modal.setAttribute('data-coming-soon-initialized','true');
        var title=document.getElementById('comingSoonTitle');
        var message=document.getElementById('comingSoonMessage');
        var lastTrigger=null;
        function closeModal(){
            modal.hidden=true;
            document.body.classList.remove('coming-soon-open');
            if(lastTrigger){lastTrigger.focus();}
        }
        document.addEventListener('click',function(event){
            var trigger=event.target.closest('.coming-soon-trigger');
            if(!trigger){return;}
            // .coming-soon-trigger is reserved for the client dashboard
            // (sidebar rows and empty store sections). The public navbar
            // uses .coming-soon-link for its real /services pages, so it
            // never matches this selector and keeps navigating normally.
            event.preventDefault();
            lastTrigger=trigger;
            var service=trigger.getAttribute('data-service')||'This service';
            title.textContent=service+' is coming soon';
            message.textContent='We are carefully preparing '+service+' for you. It will be available soon.';
            modal.hidden=false;
            document.body.classList.add('coming-soon-open');
            modal.querySelector('[data-coming-soon-close]').focus();
        });
        modal.querySelectorAll('[data-coming-soon-close]').forEach(function(button){
            button.addEventListener('click',closeModal);
        });
        modal.addEventListener('click',function(event){
            if(event.target===modal){closeModal();}
        });
        document.addEventListener('keydown',function(event){
            if(event.key==='Escape'&&!modal.hidden){closeModal();}
        });
    }());
</script>
