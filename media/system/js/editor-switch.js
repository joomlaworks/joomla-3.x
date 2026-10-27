/**
 * @copyright  (C) 2026 JoomlaWorks Ltd. and this project's contributors
 * @license    GNU General Public License version 2 or later; see LICENSE.md
 */

/*
 * The editor switcher (layout joomla.editors.switcher): the chosen editor is posted as editor_switch and kept for the session.
 * With the form's reload task (data-editor-reload="item.reload" on the form; core components' edit forms find theirs from
 * their Save button), the form is posted to it, so what wasn't saved comes back in the other editor. Otherwise, after the
 * visitor confirms, the switch is posted on its own and the page loads again.
 */
(() => {
	'use strict';

	const TASK = /submitbutton\(\s*['"]([A-Za-z0-9_]+)\.(?:apply|save)['"]/;

	const reloadTask = (switcher, form) => {
		if (!form || !form.elements.task) {
			return '';
		}

		if (form.hasAttribute('data-editor-reload')) {
			return form.getAttribute('data-editor-reload');
		}

		if (switcher.getAttribute('data-core') !== '1') {
			return '';
		}

		for (const element of document.querySelectorAll('[onclick*="submitbutton"]')) {
			const match = TASK.exec(element.getAttribute('onclick'));

			if (match) {
				return match[1] + '.reload';
			}
		}

		return '';
	};

	const hidden = (form, name, value) => {
		let input = form.querySelector('input[type="hidden"][name="' + name + '"]');

		if (!input) {
			input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			form.appendChild(input);
		}

		input.value = value;
	};

	const submit = (form, task) => {
		if (window.Joomla && typeof window.Joomla.submitform === 'function') {
			window.Joomla.submitform(task, form);

			return;
		}

		// As Joomla.submitform() does: a submit button, so the editors' submit listeners write their content first
		form.elements.task.value = task;

		const button = document.createElement('input');
		button.type = 'submit';
		button.style.display = 'none';
		form.appendChild(button).click();
		form.removeChild(button);
	};

	const switchTo = (switcher, editor) => {
		const form = switcher.closest('form');
		const task = reloadTask(switcher, form);

		if (task) {
			hidden(form, 'editor_switch', editor);
			submit(form, task);

			return true;
		}

		if (!window.confirm(switcher.getAttribute('data-confirm'))) {
			return false;
		}

		const post = document.createElement('form');
		post.method = 'post';
		post.action = window.location.href;
		post.hidden = true;
		hidden(post, 'editor_switch', editor);
		hidden(post, 'editor_switch_return', '1');
		hidden(post, switcher.getAttribute('data-token'), '1');
		document.body.appendChild(post);
		post.submit();

		return true;
	};

	const close = (except) => {
		for (const details of document.querySelectorAll('.editor-switch details[open]')) {
			if (details !== except) {
				details.open = false;
			}
		}
	};

	document.addEventListener('click', (event) => {
		const button = event.target.closest('[data-editor-switch-to]');

		close(event.target.closest('.editor-switch details'));

		if (!button || button.disabled) {
			return;
		}

		const switcher = button.closest('[data-editor-switch]');

		if (switcher.classList.contains('is-switching')) {
			return;
		}

		button.closest('details').open = false;
		switcher.classList.add('is-switching');

		// Still on the page (cancelled, or the form refused to submit): the switcher may be used again
		if (!switchTo(switcher, button.getAttribute('data-editor-switch-to'))) {
			switcher.classList.remove('is-switching');
		} else {
			window.setTimeout(() => switcher.classList.remove('is-switching'), 3000);
		}
	});

	document.addEventListener('keydown', (event) => {
		const details = event.key === 'Escape' && event.target.closest ? event.target.closest('.editor-switch details[open]') : null;

		if (details) {
			details.open = false;
			details.querySelector('summary').focus();
		}
	});
})();
