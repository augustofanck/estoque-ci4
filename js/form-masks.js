(function (w, d) {
  "use strict";
  function mask(el, opts) {
    if (el && w.Inputmask) w.Inputmask(opts).mask(el);
  }

  function applyDate(root) {
    root = root || d;
    root.querySelectorAll(".date-mask").forEach(function (el) {
      var v = (el.value || "").trim();
      if (/^\d{4}-\d{2}-\d{2}$/.test(v)) {
        var p = v.split("-");
        el.value = p[2] + "/" + p[1] + "/" + p[0];
      }
    });
    if (w.Inputmask)
      w.Inputmask({
        mask: "99/99/9999",
        clearIncomplete: true,
        showMaskOnFocus: false,
        showMaskOnHover: false,
      }).mask(root.querySelectorAll(".date-mask"));
  }

  function applyCPF(root) {
    var el = (root || d).getElementById("documento");
    mask(el, {
      mask: "999.999.999-99",
      clearIncomplete: true,
      showMaskOnFocus: true,
      showMaskOnHover: false,
      onBeforePaste: function (v) {
        return (v || "").replace(/\D+/g, "");
      },
    });
  }

  function applyPhone(root) {
    var el = (root || d).getElementById("telefone");
    mask(el, {
      mask: ["(99) 9999-9999", "(99) 99999-9999"],
      keepStatic: true,
      clearIncomplete: true,
      showMaskOnFocus: true,
      showMaskOnHover: false,
      onBeforePaste: function (v) {
        return (v || "").replace(/\D+/g, "");
      },
    });
  }

  function applyCEP(root) {
    var el = (root || d).getElementById("cep");
    mask(el, {
      mask: "99999-999",
      clearIncomplete: true,
      showMaskOnFocus: true,
      showMaskOnHover: false,
      onBeforePaste: function (v) {
        return (v || "").replace(/\D+/g, "");
      },
    });
  }

  w.FormsMasks = {
    applyAll: function (root) {
      applyDate(root);
      applyCPF(root);
      applyPhone(root);
      applyCEP(root);
    },
    applyDate: applyDate,
    applyCPF: applyCPF,
    applyPhone: applyPhone,
    applyCEP: applyCEP,
  };
})(window, document);
