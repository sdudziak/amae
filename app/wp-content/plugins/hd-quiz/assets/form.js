export const form = {
	tabs: function () {
		const items = document.getElementsByClassName("hdq_quiz_tab");
		for (let i = 0; i < items.length; i++) {
			items[i].addEventListener("click", function () {
				removeActiveClass();
				this.classList.add("hdq_quiz_tab_active");
				let id = this.getAttribute("data-id");
				document.getElementById(id).classList.add("hdq_tab_content_active");
			});
		}

		function removeActiveClass() {
			let activeNav = document.getElementsByClassName("hdq_quiz_tab_active");
			while (activeNav.length > 0) {
				activeNav[0].classList.remove("hdq_quiz_tab_active");
			}

			let activeContent = document.getElementsByClassName("hdq_tab_content_active");
			while (activeContent.length > 0) {
				activeContent[0].classList.remove("hdq_tab_content_active");
			}
		}
	},
	createEditors: function () {
		// destroy old editors so we can re-init
		let editors = document.getElementsByClassName("hd_editor_input");
		for (let i = 0; i < editors.length; i++) {
			let eID = editors[i].getAttribute("id");
			tinyMCE.execCommand("mceRemoveEditor", false, eID);
		}

		setTimeout(initTINYMCE, 1600); // give it some time to load in first

		function initTINYMCE() {
			tinyMCE.init({
				mode: "textareas",
				relative_urls: false,
				remove_script_host: false,
				convert_urls: false,
				browser_spellcheck: true,
				entity_encoding: "raw",
				keep_styles: false,
				resize: true,
				content_style: "body { height: 100vh; min-height: 120px }",
				menubar: false,
				branding: false,
				wpeditimage_html5_captions: true,
				plugins: "charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview",
				wpautop: true,
				indent: false,
				toolbar1: "formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,dfw,wp_adv",
				toolbar2: "strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help",
				toolbar3: "",
				toolbar4: "",
				tabfocus_elements: "content-html,save-post",
				wp_autoresize_on: false,
				add_unload_trigger: false,
				block_formats: "Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Code=code",
			});
		}
	},
};
