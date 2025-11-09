(function (window, undefined) {
	// Utils
	var turndownService;

	function formData_to_json(formData){
		let object = {};
		formData.forEach((value, key) => {
			// Reflect.has in favor of: object.hasOwnProperty(key)
			if(!Reflect.has(object, key)){
				object[key] = value;
				return;
			}
			if(!Array.isArray(object[key])){
				object[key] = [object[key]];    
			}
			object[key].push(value);
		});
		return JSON.stringify(object);
	}

	if(typeof marked !== 'undefined'){
		marked.use({
			gfm: true,
		});
	}

	// let need_to_fire_change_on_editable = false;
	function format_card_html(card_wrapper_node){
		// Remove first header
		const header = card_wrapper_node.querySelector('h1');
		if(header){
			header.remove();
		}
		// check li without checkbox and add it
		const custom_lists = card_wrapper_node.querySelectorAll('ul:has(li > input[type="checkbox"])');
		custom_lists.forEach(function(custom_list){
			let items_to_update = custom_list.querySelectorAll('li:not(:has(input[type="checkbox"]))');
			items_to_update.forEach(function(item_to_update){
				item_to_update
				let checkbox = document.createElement('input');
				checkbox.type = 'checkbox';
				item_to_update.prepend(checkbox);
			});
		});

		const checkbox_list_items = card_wrapper_node.querySelectorAll('li > input[type="checkbox"]');
		checkbox_list_items.forEach(function(checkbox_list_item){
			let parent = checkbox_list_item.parentNode;

			if(parent.querySelectorAll('.li_content').length === 0){
				// Get checkbox part
				checkbox = parent.removeChild(checkbox_list_item);

				// Edit checkbox to custom
				checkbox.disabled = false;
				checkbox.classList.add('custom_checkbox');

				// Create a span for custom checkbox display
				let checkbox_span = document.createElement('span');
				checkbox_span.classList.add('custom_checkbox_placeholder');
				// checkbox.after(checkbox_span);
				checkbox_span.addEventListener('click',function(){
					let check = this.previousElementSibling;
					check.checked = !check.checked;
				});

				// Set a span around the rest of li
				let content_span = document.createElement('span');
				content_span.classList.add('li_content');
				content_span.innerHTML = parent.innerHTML;

				// Gety everything back as li > checkbox + checkbox_span + content_span
				parent.textContent = '';
				parent.append(checkbox,checkbox_span,content_span);
			}
		});
		// Add editable param
		// const editable_nodes = card_wrapper_node.querySelectorAll('p, li');
		const editable_nodes = card_wrapper_node.querySelectorAll('li > .li_content');
		editable_nodes.forEach(function(editable_node){
			editable_node.setAttribute('contenteditable','plaintext-only');
			// Add event on editable nodes
			if(editable_node.classList.contains('li_content')){
				// editable_node.addEventListener('input', function() {
				// 	need_to_fire_change_on_editable = true;
				// });
				editable_node.addEventListener('focusin', function() {
					// Transform html as markdown
					editable_node.innerText = turndownService.turndown(editable_node.innerHTML);
				});
				editable_node.addEventListener('focusout', function() {
					// Split by newline
					let content_parts = editable_node.innerText.split(/\n/);

					// Remove first and set content
					let first_content = content_parts.shift();
					editable_node.innerHTML = marked.parseInline(first_content.replace(/(\t|\s{2,})/gm, ' ').trim());

					// Foreach (not first) => add a custom li
					content_parts.reverse();
					let parent_li = editable_node.closest('li');
					content_parts.forEach(function(content_part){
						if(content_part.trim() !== ''){
							let new_li = document.createElement('li');
							new_li.innerHTML = marked.parseInline(content_part.replace(/(\t|\s{2,})/gm, ' ').trim());
							parent_li.after(new_li);
						}
					});

					format_card_html(card_wrapper);
				});
			}
		});
	}

	// On load
	document.addEventListener('DOMContentLoaded', function(event){
		// Ressources
		const root = document.documentElement;
		const main_utils_ajax_error_message = document.querySelector('#main_utils .utils_error');

		// Add card
		const add_card_form = document.querySelector('#main_utils form[name="add_card_form"]');
		const add_card_input = document.querySelector('input[name="card_title"]');
		const add_card_button = document.querySelector('button[name="add_card"]');
		if(add_card_form && add_card_button){
			add_card_button.addEventListener('click',function(e){
				e.preventDefault();
				let data = new FormData(add_card_form);
				fetch(
					this.getAttribute('data-url'),
					{
						'method': 'post',
						'body': formData_to_json(data),
					}
				)
				.then((response) => {
					if (!response.ok) {
						throw new Error(`HTTP error: ${response.status}`);
					}
					return response.json();
				})
				.then((json_data) => {
					// console.log(json_data);
					if(json_data.error){
						main_utils_ajax_error_message.textContent = json_data.error_message;
					} else if(json_data.card_url !== ''){
						// history.pushState({}, '', json_data.card_url);
						window.location.href = json_data.card_url;
					}
				})
				.catch((error) => {
					main_utils_ajax_error_message.textContent = error;
				});
			});
		}
		if(add_card_input){
			add_card_input.addEventListener('keyup',function(e){
				if(e.isComposing) {
					return;
				}
				if(this.value !== ''){
					main_utils_ajax_error_message.textContent = '\u00A0';
				}
			});
		}

		// Add item
		const add_item_form_wrapper = document.querySelector('[data-toggle-target-id="add_item_form_wrapper"]');
		const add_item_form = document.querySelector('form[name="add_item_form"]');
		const add_item_input = document.querySelector('input[name="item_content"]');
		const add_item_button = document.querySelector('button[name="add_item"]');
		const card_wrapper = document.querySelector('#card_wrapper');
		if(add_item_form && add_item_button){
			add_item_button.addEventListener('click',function(e){
				e.preventDefault();
				let data = new FormData(add_item_form);
				fetch(
					this.getAttribute('data-url'),
					{
						'method': 'post',
						'body': formData_to_json(data),
					}
				)
				.then((response) => {
					if (!response.ok) {
						throw new Error(`HTTP error: ${response.status}`);
					}
					return response.json();
				})
				.then((json_data) => {
					// console.log(json_data);
					if(json_data.error){
						main_utils_ajax_error_message.textContent = json_data.error_message;
					} else if(json_data.card_content !== '' && card_wrapper){
						card_wrapper.innerHTML = json_data.card_content;
						format_card_html(card_wrapper);
						add_item_input.value = '';
						hide(add_item_form_wrapper);
						hide(main_overlay);
						card_wrapper.scrollTop = card_wrapper.scrollHeight;
					}
				})
				.catch((error) => {
					main_utils_ajax_error_message.textContent = error;
				});
			});
		}
		if(add_item_input){
			add_item_input.addEventListener('keyup',function(e){
				if(e.isComposing) {
					return;
				}
				if(this.value !== ''){
					main_utils_ajax_error_message.textContent = '\u00A0';
				}
			});
		}

		// Card display and save
		if(card_wrapper){
			format_card_html(card_wrapper);
			turndownService = new TurndownService({
				'headingStyle': 'atx',
				'bulletListMarker': '-'
			});
			turndownService.addRule('checkbox', {
				filter: function (node, options) {
					return (
						node.nodeName === 'INPUT' &&
						node.getAttribute('type') == 'checkbox'
					);
				},
				replacement: function (content, node, options) {
					return '['+(node.checked ? 'x' : ' ')+']';
				}
			});
		}
		const save_card_button = document.querySelector('button[name="save_card"]');
		if(save_card_button && card_wrapper) {
			save_card_button.addEventListener('click',function(e){
				e.preventDefault();

				let data = {
					'card_content': '# '+document.querySelector('#center_header h1').textContent+"\r\n"+turndownService.turndown(card_wrapper),
					'card_id': card_wrapper.getAttribute('data-card_id'),
				}
				fetch(
					this.getAttribute('data-url'),
					{
						'method': 'post',
						'body': JSON.stringify(data),
					}
				)
				.then((response) => {
					if (!response.ok) {
						throw new Error(`HTTP error: ${response.status}`);
					}
					return response.json();
				})
				.then((json_data) => {
					// console.log(json_data);
					if(json_data.error){
						main_utils_ajax_error_message.textContent = json_data.error_message;
					} else if(json_data.card_content !== '' && card_wrapper){
						card_wrapper.innerHTML = json_data.card_content;
						format_card_html(card_wrapper);
					}
				})
				.catch((error) => {
					main_utils_ajax_error_message.textContent = error;
				});
			});
		}
		
		// UTILS //
			// Toggle
		const main_overlay = document.querySelector('#main_overlay');
		const toggle_buttons = document.querySelectorAll('[data-toggle-target]');
		toggle_buttons.forEach(function(toggle_button){
			toggle_button.addEventListener('click',function(){
				let targets = document.querySelectorAll('[data-toggle-target-id="'+toggle_button.dataset.toggleTarget+'"');
				targets.forEach(function(target){
					if(target.classList.contains('visible')){
						hide(main_overlay);
						hide(target);
					} else {
						show(main_overlay);
						show(target);
						let first_input = target.querySelector('input:not([hidden])');
						if(first_input){
							first_input.focus();
						}
					}
				});
			});
		});
		const targets = document.querySelectorAll('[data-toggle-target-id]');
		main_overlay.addEventListener('click',function(){
			hide(main_overlay);
			targets.forEach(function(target){
				hide(target);
			});
		});
			// show / hide
		let hide = function(node){
			node.classList.remove('visible');
			node.classList.add('invisible');
		}
		let show = function(node){
			node.classList.remove('invisible');
			node.classList.add('visible');
		}
	});
})(window);


