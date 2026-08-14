<?php
// Shared "show / hide password" behaviour. Included by both the app layout
// (layouts/footer.php) and the standalone login page, which has no layout of
// its own — so every password or PIN box in the system gets the same eye
// button without each view having to add one.
//
// Note this reveals what the user is *typing*. Stored passwords are bcrypt
// hashes and cannot be read back by anyone, staff included; an admin who needs
// to give someone their password sets a new one under Login Accounts.
?>
<script>
(function(){
  var EYE = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">'
          + '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>'
          + '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
  var EYE_OFF = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">'
          + '<path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243"/></svg>';

  function attach(input){
    if (input.dataset.pwToggle === 'done' || input.hasAttribute('data-no-reveal')) { return; }
    input.dataset.pwToggle = 'done';

    var wrap = document.createElement('span');
    wrap.className = 'pw-field';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    var btn = document.createElement('button');
    // type="button" matters: inside a login form a bare <button> would submit it.
    btn.type = 'button';
    btn.className = 'pw-toggle';
    btn.innerHTML = EYE;
    btn.setAttribute('aria-label', 'Show password');
    btn.title = 'Show password';
    btn.addEventListener('click', function(){
      var shown = input.getAttribute('type') === 'text';
      input.setAttribute('type', shown ? 'password' : 'text');
      btn.innerHTML = shown ? EYE : EYE_OFF;
      var label = shown ? 'Show password' : 'Hide password';
      btn.setAttribute('aria-label', label);
      btn.title = label;
      input.focus();
    });
    wrap.appendChild(btn);
  }

  function scan(root){
    (root || document).querySelectorAll('input[type="password"]').forEach(attach);
  }
  scan(document);
  // Modal forms are reset and re-shown rather than reloaded, and a revealed
  // field must not stay revealed the next time the modal is opened.
  document.querySelectorAll('.modal-overlay form').forEach(function(form){
    form.addEventListener('reset', function(){
      form.querySelectorAll('.pw-field input[type="text"]').forEach(function(el){
        el.setAttribute('type', 'password');
        var btn = el.parentNode.querySelector('.pw-toggle');
        if (btn) { btn.innerHTML = EYE; btn.setAttribute('aria-label', 'Show password'); btn.title = 'Show password'; }
      });
    });
  });
})();
</script>
