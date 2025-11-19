(function () {
  function sendPlayed(lessonId) {
    if (!window.audiohubProgress || !audiohubProgress.logged_in) return;
    var fd = new FormData();
    fd.append("action", "audiohub_lesson_play");
    fd.append("lesson_id", lessonId);
    fd.append("nonce", audiohubProgress.nonce);
    fetch(audiohubProgress.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      body: fd,
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (json) {
        if (!json || !json.success) return;
        var id = "ah-count-" + lessonId;
        var el = document.getElementById(id);
        if (el) el.textContent = json.data.plays + " / " + json.data.required;
        // Podbij badge na liście lekcji
        var counters = document.querySelectorAll(
          '.ah-counter[data-lesson-id="' + lessonId + '"]',
        );
        counters.forEach(function (c) {
          c.textContent = json.data.plays + " / " + json.data.required;
        });
      })
      .catch(function (_) {});
  }

  function hook(el) {
    if (!el || el.dataset.ahHooked) return;
    var lid = el.getAttribute("data-lesson-id");
    if (!lid) return;
    el.dataset.ahHooked = "1";
    el.addEventListener(
      "ended",
      function () {
        sendPlayed(lid);
      },
      { passive: true },
    );
  }

  document.addEventListener("DOMContentLoaded", function () {
    // Hook audio/video z atrybutem data-lesson-id
    document
      .querySelectorAll("audio[data-lesson-id], video[data-lesson-id]")
      .forEach(hook);
    // Obsługa dynamicznych wstawek z Gutenberga
    var obs = new MutationObserver(function (muts) {
      muts.forEach(function (m) {
        m.addedNodes &&
          m.addedNodes.forEach(function (n) {
            if (n.querySelectorAll) {
              n.querySelectorAll(
                "audio[data-lesson-id], video[data-lesson-id]",
              ).forEach(hook);
            }
            if (
              n.matches &&
              n.matches("audio[data-lesson-id], video[data-lesson-id]")
            )
              hook(n);
          });
      });
    });
    obs.observe(document.documentElement, { childList: true, subtree: true });
  });
})();
