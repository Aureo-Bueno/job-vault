(function () {
  'use strict';

  // Show/hide password: <button data-toggle-password="#input-id">
  document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
    button.addEventListener('click', function () {
      var input = document.querySelector(button.getAttribute('data-toggle-password'));
      var icon = button.querySelector('i');
      if (!input || !icon) {
        return;
      }

      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      icon.classList.toggle('bi-eye', !show);
      icon.classList.toggle('bi-eye-slash', show);
      button.setAttribute('aria-label', show ? 'Ocultar senha' : 'Mostrar senha');
    });
  });

  // Account type toggle on sign-up (Candidato vs Empresa)
  var accountRadios = document.querySelectorAll('[data-account-type]');
  var companyBox = document.getElementById('company-signup-fields');
  var companyNameInput = document.getElementById('company-name-signup');
  if (accountRadios.length && companyBox && companyNameInput) {
    var syncAccountType = function () {
      var checked = document.querySelector('[data-account-type]:checked');
      var isCompany = checked && checked.getAttribute('data-account-type') === 'company';
      companyBox.classList.toggle('d-none', !isCompany);
      companyNameInput.required = Boolean(isCompany);
      if (!isCompany) {
        companyNameInput.classList.remove('is-invalid');
      }
    };
    accountRadios.forEach(function (radio) {
      radio.addEventListener('change', syncAccountType);
    });
    syncAccountType();
  }

  // Interactive topic tags picker: <div data-topic-picker>
  document.querySelectorAll('[data-topic-picker]').forEach(function (picker) {
    var hidden = picker.querySelector('[data-topic-hidden]');
    var listEl = picker.querySelector('[data-topic-list]');
    var textInput = picker.querySelector('[data-topic-input]');
    var addBtn = picker.querySelector('[data-topic-add-btn]');
    var suggestions = picker.querySelectorAll('[data-topic-suggest]');

    if (!hidden || !listEl || !textInput) {
      return;
    }

    var normalizeKey = function (value) {
      return String(value || '').trim().toLowerCase().replace(/\./g, '');
    };

    var topics = (hidden.value ? hidden.value.split(',') : [])
      .map(function (t) { return t.trim(); })
      .filter(Boolean);

    var render = function () {
      hidden.value = topics.join(', ');
      listEl.innerHTML = '';

      if (topics.length === 0) {
        var empty = document.createElement('span');
        empty.className = 'text-muted small py-1';
        empty.textContent = 'Nenhum tópico adicionado ainda.';
        listEl.appendChild(empty);
      } else {
        topics.forEach(function (topic) {
          var chip = document.createElement('span');
          chip.className = 'jv-chip';
          chip.appendChild(document.createTextNode(topic + ' '));

          var btn = document.createElement('button');
          btn.type = 'button';
          btn.setAttribute('aria-label', 'Remover ' + topic);
          btn.innerHTML = '<i class="bi bi-x-lg"></i>';
          btn.addEventListener('click', function () {
            removeTopic(topic);
          });

          chip.appendChild(btn);
          listEl.appendChild(chip);
        });
      }

      var activeKeys = {};
      topics.forEach(function (t) { activeKeys[normalizeKey(t)] = true; });

      suggestions.forEach(function (btn) {
        var label = btn.getAttribute('data-topic-suggest') || '';
        var added = Boolean(activeKeys[normalizeKey(label)]);
        btn.classList.toggle('is-added', added);
        var icon = btn.querySelector('i');
        if (icon) {
          icon.className = 'bi ' + (added ? 'bi-check2' : 'bi-plus');
        }
      });
    };

    var addTopicsFromRaw = function (raw) {
      String(raw || '').split(/[,;\n]+/).forEach(function (piece) {
        var clean = piece.trim().replace(/\s+/g, ' ');
        if (!clean) {
          return;
        }
        var key = normalizeKey(clean);
        var exists = topics.some(function (existing) {
          return normalizeKey(existing) === key;
        });
        if (!exists && clean.length <= 60) {
          topics.push(clean);
        }
      });
      render();
    };

    var removeTopic = function (target) {
      var key = normalizeKey(target);
      topics = topics.filter(function (existing) {
        return normalizeKey(existing) !== key;
      });
      render();
    };

    textInput.addEventListener('keydown', function (event) {
      if (event.key === 'Enter' || event.key === ',') {
        event.preventDefault();
        if (textInput.value.trim() !== '') {
          addTopicsFromRaw(textInput.value);
          textInput.value = '';
        }
      }
    });

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        if (textInput.value.trim() !== '') {
          addTopicsFromRaw(textInput.value);
          textInput.value = '';
          textInput.focus();
        }
      });
    }

    suggestions.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var label = btn.getAttribute('data-topic-suggest') || '';
        var key = normalizeKey(label);
        var exists = topics.some(function (existing) {
          return normalizeKey(existing) === key;
        });
        if (exists) {
          removeTopic(label);
        } else {
          addTopicsFromRaw(label);
        }
      });
    });

    var parentForm = picker.closest('form');
    if (parentForm) {
      parentForm.addEventListener('submit', function () {
        if (textInput.value.trim() !== '') {
          addTopicsFromRaw(textInput.value);
          textInput.value = '';
        }
      });
    }

    render();
  });

  // Client-side validation only highlights errors (red). Fields are never painted green:
  // a valid format does not mean the server accepted the data (e.g. wrong password).
  var markField = function (field) {
    if (field.closest('.d-none')) {
      field.classList.remove('is-invalid');
      return;
    }
    var invalid = !field.checkValidity();
    field.classList.toggle('is-invalid', invalid);
    var group = field.closest('.input-group');
    if (group) {
      group.classList.toggle('is-invalid', invalid);
    }
  };

  document.querySelectorAll('.needs-validation').forEach(function (form) {
    var fields = form.querySelectorAll('input:not([type=hidden]), select, textarea');

    form.addEventListener('submit', function (event) {
      fields.forEach(markField);
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        var firstInvalid = form.querySelector(':invalid');
        if (firstInvalid) {
          firstInvalid.focus();
        }
      }
    });

    fields.forEach(function (field) {
      var eventName = field.type === 'checkbox' || field.type === 'radio' ? 'change' : 'input';
      field.addEventListener(eventName, function () {
        if (field.classList.contains('is-invalid')) {
          markField(field);
        }
      });
    });
  });

  // Confirmation for destructive actions: <form data-confirm="...">
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.confirm(form.getAttribute('data-confirm'))) {
        event.preventDefault();
      }
    });
  });

  // Success/info alerts close themselves; errors stay until dismissed.
  document.querySelectorAll('.alert[data-auto-close="true"]').forEach(function (alert) {
    setTimeout(function () {
      if (window.bootstrap) {
        window.bootstrap.Alert.getOrCreateInstance(alert).close();
      }
    }, 5000);
  });
})();
