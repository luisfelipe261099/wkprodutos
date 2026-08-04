/**
 * Searchable Select - combobox com busca, sem dependencias externas.
 *
 * Uso:
 *   <select data-searchable data-search-placeholder="Buscar empresa...">...</select>
 *
 * O <select> original continua no formulario (name/value/required), entao o envio
 * do form e a validacao nativa do navegador funcionam normalmente. O componente
 * apenas desenha um controle acessivel por cima e sincroniza os dois sentidos.
 */
(function () {
    'use strict';

    var idSeq = 0;

    function normalize(text) {
        return (text || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function escapeHtml(text) {
        return (text || '')
            .toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function SearchableSelect(select) {
        if (!select || select.dataset.searchableReady === '1') {
            return;
        }
        select.dataset.searchableReady = '1';

        var self = this;
        idSeq += 1;

        this.select = select;
        this.placeholder = select.dataset.searchPlaceholder || 'Buscar...';
        this.emptyText = select.dataset.searchEmpty || 'Nenhum resultado encontrado';
        this.activeIndex = -1;

        this.wrapper = document.createElement('div');
        this.wrapper.className = 'ss-wrapper';

        this.toggle = document.createElement('button');
        this.toggle.type = 'button';
        this.toggle.className = 'ss-toggle form-select';
        this.toggle.setAttribute('aria-haspopup', 'listbox');
        this.toggle.setAttribute('aria-expanded', 'false');

        this.toggleLabel = document.createElement('span');
        this.toggleLabel.className = 'ss-toggle-label';
        this.toggle.appendChild(this.toggleLabel);

        this.panel = document.createElement('div');
        this.panel.className = 'ss-panel';
        this.panel.hidden = true;

        this.search = document.createElement('input');
        this.search.type = 'text';
        this.search.className = 'ss-search';
        this.search.placeholder = this.placeholder;
        this.search.autocomplete = 'off';

        this.list = document.createElement('ul');
        this.list.className = 'ss-list';
        this.list.id = 'ss-list-' + idSeq;
        this.list.setAttribute('role', 'listbox');

        this.panel.appendChild(this.search);
        this.panel.appendChild(this.list);

        select.parentNode.insertBefore(this.wrapper, select);
        this.wrapper.appendChild(select);
        this.wrapper.appendChild(this.toggle);
        this.wrapper.appendChild(this.panel);
        select.classList.add('ss-native');

        this.toggle.addEventListener('click', function () {
            self.isOpen() ? self.close() : self.open();
        });

        this.search.addEventListener('input', function () {
            self.renderOptions(self.search.value);
        });

        this.search.addEventListener('keydown', function (event) {
            self.onSearchKeydown(event);
        });

        this.toggle.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                self.open();
            }
        });

        this.list.addEventListener('click', function (event) {
            var item = event.target.closest('.ss-option');
            if (item && !item.classList.contains('ss-option-disabled')) {
                self.commit(item.dataset.value);
            }
        });

        document.addEventListener('click', function (event) {
            if (!self.wrapper.contains(event.target)) {
                self.close();
            }
        });

        // Mantem o botao em sincronia caso o valor mude via codigo.
        select.addEventListener('change', function () {
            self.syncLabel();
        });

        this.syncLabel();
    }

    SearchableSelect.prototype.isOpen = function () {
        return !this.panel.hidden;
    };

    SearchableSelect.prototype.open = function () {
        if (this.select.disabled) {
            return;
        }
        this.panel.hidden = false;
        this.toggle.setAttribute('aria-expanded', 'true');
        this.wrapper.classList.add('ss-open');
        this.search.value = '';
        this.renderOptions('');
        this.search.focus();
    };

    SearchableSelect.prototype.close = function () {
        if (!this.isOpen()) {
            return;
        }
        this.panel.hidden = true;
        this.toggle.setAttribute('aria-expanded', 'false');
        this.wrapper.classList.remove('ss-open');
        this.activeIndex = -1;
    };

    SearchableSelect.prototype.currentOption = function () {
        return this.select.options[this.select.selectedIndex] || null;
    };

    SearchableSelect.prototype.syncLabel = function () {
        var option = this.currentOption();
        var text = option ? option.text.trim() : '';
        var isPlaceholder = !option || option.value === '';

        this.toggleLabel.textContent = text || this.placeholder;
        this.toggle.classList.toggle('ss-toggle-placeholder', isPlaceholder);
        this.toggle.disabled = this.select.disabled;
    };

    SearchableSelect.prototype.renderOptions = function (term) {
        var normalizedTerm = normalize(term);
        var options = Array.prototype.slice.call(this.select.options);
        var selectedValue = this.select.value;
        var html = '';
        var visible = 0;

        options.forEach(function (option) {
            var haystack = normalize(option.text + ' ' + (option.dataset.keywords || ''));
            if (normalizedTerm && haystack.indexOf(normalizedTerm) === -1) {
                return;
            }

            visible += 1;
            var classes = 'ss-option';
            if (option.value === selectedValue) {
                classes += ' ss-option-selected';
            }
            if (option.disabled) {
                classes += ' ss-option-disabled';
            }

            var hint = option.dataset.hint
                ? '<span class="ss-option-hint">' + escapeHtml(option.dataset.hint) + '</span>'
                : '';
            html += '<li class="' + classes + '" role="option" data-value="' +
                escapeHtml(option.value) + '" aria-selected="' +
                (option.value === selectedValue ? 'true' : 'false') + '">' +
                '<span class="ss-option-text">' + escapeHtml(option.text) + '</span>' + hint + '</li>';
        });

        if (visible === 0) {
            html = '<li class="ss-empty">' + escapeHtml(this.emptyText) + '</li>';
        }

        this.list.innerHTML = html;
        this.activeIndex = -1;
        this.list.scrollTop = 0;
    };

    SearchableSelect.prototype.items = function () {
        return Array.prototype.slice.call(this.list.querySelectorAll('.ss-option:not(.ss-option-disabled)'));
    };

    SearchableSelect.prototype.move = function (delta) {
        var items = this.items();
        if (!items.length) {
            return;
        }

        this.activeIndex = (this.activeIndex + delta + items.length) % items.length;
        items.forEach(function (item, index) {
            item.classList.toggle('ss-option-active', index === this.activeIndex);
        }, this);
        items[this.activeIndex].scrollIntoView({ block: 'nearest' });
    };

    SearchableSelect.prototype.onSearchKeydown = function (event) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            this.move(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            this.move(-1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            var items = this.items();
            var target = this.activeIndex >= 0 ? items[this.activeIndex] : items[0];
            if (target) {
                this.commit(target.dataset.value);
            }
        } else if (event.key === 'Escape') {
            event.preventDefault();
            this.close();
            this.toggle.focus();
        }
    };

    SearchableSelect.prototype.commit = function (value) {
        this.select.value = value;
        this.syncLabel();
        this.close();
        this.toggle.focus();
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
    };

    function initAll(root) {
        (root || document).querySelectorAll('select[data-searchable]').forEach(function (select) {
            new SearchableSelect(select);
        });
    }

    window.SearchableSelect = { init: initAll };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initAll(document); });
    } else {
        initAll(document);
    }
})();
