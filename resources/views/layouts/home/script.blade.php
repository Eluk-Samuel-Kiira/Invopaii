<script>
/* ==========================================================
   Stardena Pay — shared behaviour
   ========================================================== */
(function () {
  "use strict";

  /* ---------- mobile navigation ---------- */
  var navToggle = document.getElementById("nav-toggle");
  var mobileMenu = document.getElementById("mobile-menu");

  if (navToggle && mobileMenu) {
    var closeMenu = function () {
      mobileMenu.dataset.open = "false";
      navToggle.classList.remove("is-open");
      navToggle.setAttribute("aria-expanded", "false");
    };

    navToggle.addEventListener("click", function () {
      var open = mobileMenu.dataset.open === "true";
      mobileMenu.dataset.open = String(!open);
      navToggle.classList.toggle("is-open", !open);
      navToggle.setAttribute("aria-expanded", String(!open));
    });

    mobileMenu.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", closeMenu);
    });

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && mobileMenu.dataset.open === "true") {
        closeMenu();
        navToggle.focus();
      }
    });

    window.addEventListener("resize", function () {
      if (window.innerWidth > 900 && mobileMenu.dataset.open === "true") {
        closeMenu();
      }
    });
  }

  /* ---------- sandbox widget ---------- */
  var widget = document.getElementById("widget");
  var widgetToggle = document.getElementById("widget-toggle");

  if (widget && widgetToggle) {
    widgetToggle.addEventListener("click", function () {
      var open = widget.dataset.open === "true";
      widget.dataset.open = String(!open);
      widgetToggle.setAttribute("aria-expanded", String(!open));
    });

    widget.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && widget.dataset.open === "true") {
        widget.dataset.open = "false";
        widgetToggle.setAttribute("aria-expanded", "false");
        widgetToggle.focus();
      }
    });
  }

  /* ---------- copy test key ---------- */
  var copyBtn = document.getElementById("copy-key");
  var keyEl = document.getElementById("key");

  if (copyBtn && keyEl) {
    copyBtn.addEventListener("click", function () {
      var text = keyEl.textContent.trim();

      function done() {
        copyBtn.textContent = "Copied";
        setTimeout(function () {
          copyBtn.textContent = "Copy";
        }, 1500);
      }

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(done, fallback);
      } else {
        fallback();
      }

      function fallback() {
        var input = document.createElement("textarea");
        input.value = text;
        input.setAttribute("readonly", "");
        input.style.position = "fixed";
        input.style.opacity = "0";
        document.body.appendChild(input);
        input.select();
        try {
          document.execCommand("copy");
          done();
        } catch (err) {
          copyBtn.textContent = "Press Ctrl+C";
        }
        document.body.removeChild(input);
      }
    });
  }

  /* ---------- signup form (only present on the home page) ---------- */
  var form = document.getElementById("signup-form");
  var msg = document.getElementById("form-msg");

  if (form && msg) {
    var input = form.querySelector("#email");
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var value = input.value.trim();

      if (!pattern.test(value)) {
        input.classList.add("is-invalid");
        msg.className = "form-msg is-error";
        msg.textContent = "Enter a valid work email to continue.";
        input.focus();
        return;
      }

      input.classList.remove("is-invalid");
      msg.className = "form-msg is-ok";
      msg.textContent = "Check your inbox — your test keys are on the way.";
      form.reset();

      // Replace with your real endpoint:
      // fetch("/api/signup", {
      //   method: "POST",
      //   headers: { "Content-Type": "application/json" },
      //   body: JSON.stringify({ email: value })
      // });
    });

    input.addEventListener("input", function () {
      if (input.classList.contains("is-invalid")) {
        input.classList.remove("is-invalid");
        msg.textContent = "";
        msg.className = "form-msg";
      }
    });
  }
})();
</script>