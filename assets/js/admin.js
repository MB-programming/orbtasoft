document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        e.preventDefault();
      }
    });
  });

  var flash = document.querySelector('.admin-flash');
  if (flash) {
    setTimeout(function () {
      flash.style.transition = 'opacity .4s ease';
      flash.style.opacity = '0';
    }, 4000);
  }

  /* ---------- Site content search filter ---------- */
  var contentSearch = document.getElementById('contentSearch');
  if (contentSearch) {
    var rows = document.querySelectorAll('.content-row');
    var groups = document.querySelectorAll('.content-group');
    contentSearch.addEventListener('input', function () {
      var query = contentSearch.value.trim().toLowerCase();
      groups.forEach(function (group) {
        var groupRows = group.querySelectorAll('.content-row');
        var visibleCount = 0;
        groupRows.forEach(function (row) {
          var match = !query || row.getAttribute('data-search').indexOf(query) !== -1;
          row.classList.toggle('is-hidden', !match);
          if (match) visibleCount++;
        });
        group.classList.toggle('is-hidden', visibleCount === 0);
        if (query) group.setAttribute('open', '');
      });
    });
  }

  /* ---------- Chat: emoji picker, attachments, voice notes ---------- */
  initChatForms();
});

/* Shared by the client account chat (main.js) and the admin chat (admin.js). */
function initChatForms() {
  document.querySelectorAll('.chat-form').forEach(function (form) {
    var textarea = form.querySelector('textarea[name="body"]');
    var emojiBtn = form.querySelector('.chat-emoji-btn');
    var emojiPanel = form.querySelector('.chat-emoji-panel');

    if (emojiBtn && emojiPanel && textarea) {
      emojiBtn.addEventListener('click', function (e) {
        e.preventDefault();
        emojiPanel.classList.toggle('is-open');
      });
      emojiPanel.querySelectorAll('button').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var start = textarea.selectionStart || textarea.value.length;
          var end = textarea.selectionEnd || textarea.value.length;
          textarea.value = textarea.value.slice(0, start) + btn.textContent + textarea.value.slice(end);
          textarea.focus();
          emojiPanel.classList.remove('is-open');
        });
      });
      document.addEventListener('click', function (e) {
        if (!form.contains(e.target)) emojiPanel.classList.remove('is-open');
      });
    }

    var attachBtn = form.querySelector('.chat-attach-btn');
    var attachInput = form.querySelector('input[type="file"][name="attachment"]');
    var attachLabel = form.querySelector('.chat-attach-name');
    if (attachBtn && attachInput) {
      attachBtn.addEventListener('click', function (e) {
        e.preventDefault();
        attachInput.click();
      });
      attachInput.addEventListener('change', function () {
        if (attachLabel) attachLabel.textContent = attachInput.files[0] ? attachInput.files[0].name : '';
      });
    }

    var voiceBtn = form.querySelector('.chat-voice-btn');
    if (voiceBtn && attachInput && navigator.mediaDevices && window.MediaRecorder) {
      var mediaRecorder = null;
      var chunks = [];
      var recording = false;
      voiceBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!recording) {
          navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
            chunks = [];
            mediaRecorder = new MediaRecorder(stream);
            mediaRecorder.ondataavailable = function (ev) { chunks.push(ev.data); };
            mediaRecorder.onstop = function () {
              stream.getTracks().forEach(function (t) { t.stop(); });
              var blob = new Blob(chunks, { type: 'audio/webm' });
              var file = new File([blob], 'voice-message.webm', { type: 'audio/webm' });
              var dt = new DataTransfer();
              dt.items.add(file);
              attachInput.files = dt.files;
              if (attachLabel) attachLabel.textContent = 'Voice message ready — sending…';
              form.submit();
            };
            mediaRecorder.start();
            recording = true;
            voiceBtn.classList.add('is-recording');
          }).catch(function () {
            /* Mic permission denied or unavailable — silently ignore. */
          });
        } else {
          mediaRecorder.stop();
          recording = false;
          voiceBtn.classList.remove('is-recording');
        }
      });
    }
  });
}
