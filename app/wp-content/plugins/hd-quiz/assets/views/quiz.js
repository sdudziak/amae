import { self as fetcher } from "../fetcher.js";

export const self = {
	get: async function (action, d = null) {
		console.log("getting the quiz ID " + d);
		let data = await self.getData(d);
		await self.render(data);
	},
	getData: async function (d) {
		let paged = 1;
		if (d[1]) {
			paged = d[1];
		}
		let data = {
			quiz_id: d[0],
			paged: paged,
		};
		console.log(data);
		return await fetcher("hdq_get_view_quiz", data);
	},
	render: async function (data) {
		document.getElementById("hdq_content").innerHTML = data.html;
		document.getElementById("hdq_content").scrollTo(0, 0);
		document.getElementById("hdq_loading").classList.remove("active");

		self.search.init();

		_hd.init();
		HDQ.form.createEditors();
		HDQ.form.tabs();

		document.getElementById("hdq_copy_shortcode").addEventListener("click", function () {
			const el = this;
			if (el.classList.contains("active")) {
				return;
			}
			el.classList.add("active");
			let shortcode = el.innerText;
			navigator.clipboard.writeText(el.innerText);

			el.innerText = "copied to clipboard";
			setTimeout(function () {
				el.innerText = shortcode;
				el.classList.remove("active");
			}, 3000);
		});

		if (data.type === "personality") {
			self.personality.init();
		}

		HDQ.sort.init();

		self.question_order.init();

		const delete_el = document.getElementById("hd_delete_quiz");
		if (!delete_el) {
			return;
		}
		delete_el.addEventListener("click", async function () {
			const message = `You are about to delete this entire quiz and all attached questions. Continue?`; // HDQ_LOCALIZE
			if (confirm(message)) {
				deleteQuiz(this);
			}
		});

		async function deleteQuiz(el) {
			let data = {
				quiz_id: parseInt(el.getAttribute("data-quiz")),
			};
			let res = await fetcher("hdq_delete_quiz", data);
			console.log(res);
			HDQ.router.views.dashboard.get(null, []);
		}
		return data;
	},
	search: {
		init: function () {
			const questions = document.getElementsByClassName("hdq_quiz_item");
			if (questions.length <= 25) {
				return;
			}

			const searchEl = `<div id = "hdq_quiz_tabs_search_wrapper"><span class="hd_tooltip_item">?<span class="hd_tooltip"><div class="hd_tooltip_content">Press Enter to confirm your search for question titles. This feature is currently experimental.</div></span></span> <input type = "search" placeholder = "search..." id = "hdq_questions_search"/></div>`;
			document.getElementById("hdq_quiz_tabs_labels").insertAdjacentHTML("beforeend", searchEl);

			document.getElementById("hdq_questions_search").addEventListener("keyup", self.search.filter);
			document.getElementById("hdq_questions_search").addEventListener("click", function () {
				if (this.value !== "") {
					this.value = "";
					self.search.clear();
				}
			});
		},
		filter: function (ev) {
			if (ev.keyCode !== 13) {
				return;
			}

			const el = document.getElementById("hdq_questions_search");
			if (el.value.length === 0) {
				self.search.clear();
				return;
			}

			if (el.value.length >= 3) {
				const v = el.value.toLocaleUpperCase();
				const questions = document.getElementsByClassName("hdq_quiz_item");
				for (let i = 0; i < questions.length; i++) {
					let title = questions[i].getElementsByTagName("span")[1].innerText.toLocaleUpperCase();
					if (!title.includes(v)) {
						questions[i].classList.add("hdq_hidden");
					} else {
						questions[i].classList.remove("hdq_hidden");
					}
				}
			}
		},
		clear: function () {
			const questions = document.getElementsByClassName("hdq_quiz_item");
			for (let i = 0; i < questions.length; i++) {
				questions[i].classList.remove("hdq_hidden");
			}
		},
	},
	question_order: {
		init: function () {
			_hd.validate.field.hdq_field_question_order = self.question_order.validate; // set HDFields validation function
		},
		validate: async function (field) {
			let data = await self.question_order.getData(field);

			let v = {
				value: data,
				status: true,
			};
			return v;
		},
		getData: async function () {
			const questions = document.getElementsByClassName("hdq_quiz_question");
			let data = [];
			for (let i = 0; i < questions.length; i++) {
				let question_id = parseInt(questions[i].getAttribute("data-id"));
				data.push(question_id);
			}
			return data;
		},
	},
	personality: {
		saved: {
			hasAdded: false,
			hasSaved: false,
		},
		init: function () {
			// set HDFields validation function
			_hd.validate.field.hdq_field_personality_results = self.personality.validate;

			document.getElementById("hdq_add_new_personality_outcome").addEventListener("click", function () {
				self.personality.create();
			});

			self.personality.remove.init();

			document.getElementById("hd_save").addEventListener("click", function (event) {
				self.personality.saved.hasSaved = true; // note, not 100% accurate since we are not taking validation into account
			});

			document.getElementById("hdq_add_new_quiz").addEventListener("click", function (event) {
				if (self.personality.saved.hasAdded && !self.personality.saved.hasSaved) {
					if (document.getElementsByClassName("hdq_outcome_label").length > 1) {
						event.preventDefault();
						let status = confirm("You have added a new outcome but have not saved. Contunue to adding a new question?");
						if (status) {
							window.location = this.href;
						}
					}
				}
			});
		},
		remove: {
			init: function () {
				const items = document.getElementsByClassName("hdq_remove_outcome");
				for (let i = 0; i < items.length; i++) {
					items[i].addEventListener("click", function () {
						this.parentElement.remove();
					});
				}
			},
		},
		validate: async function (field) {
			await tinyMCE.triggerSave();
			const id = field.getAttribute("id");
			const data = [];

			const labels = field.getElementsByClassName("hdq_outcome_label");
			const contents = field.getElementsByClassName("hd_editor_input");
			for (let i = 0; i < labels.length; i++) {
				data.push({
					label: labels[i].value,
					id:
						labels[i].value
							.replace(/[`~!@#$%^&*()_|+\-=?;:'",.<>\{\}\[\]\\\/]/gi, "")
							.replaceAll(" ", "_")
							.toLowerCase() +
						"" +
						i, // replace special characters, and ensure unique
					content: contents[i].value,
				});
			}

			// make sure each label exists
			let valid = true;
			for (let i = 0; i < data.length; i++) {
				if (data[i].label == "") {
					valid = false;
					labels[i].classList.add("hd_error");
				} else {
					labels[i].classList.remove("hd_error");
				}
			}

			let v = {
				value: data,
				status: valid,
			};
			return v;
		},
		create: function () {
			self.personality.saved.hasAdded = true;
			let countOutcomes = document.getElementById("personality_results").getElementsByClassName("hd_input_item").length;
			countOutcomes = String.fromCharCode(countOutcomes + "A".charCodeAt(0));
			const html = `<div class="hd_input_item">
	<div class="hdq_remove_outcome" title="Remove this outcome">x</div>
	<label class="hd_input_label" for="hdq_result_${countOutcomes}"><span class="hd_required_icon"></span> Outcome title <span class="hd_tooltip_item">?<span class="hd_tooltip"><div class="hd_tooltip_content">NOTE: If you rename this outcome, you will need to re-edit your questions to set the correct answer.</div></span></span></label>
	<input type="text" data-type="text" data-required="required" class="hd_input hdq_outcome_label" id="hdq_result_${countOutcomes}" value="Result ${countOutcomes}" placeholder="Result title..." data-tab="Results" />

	<div id="wp-hdq_result_content_result_${countOutcomes}-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
		<link rel="stylesheet" id="dashicons-css" href="https://hdquiz.local/wp-includes/css/dashicons.min.css?ver=6.6.2" media="all" />
		<link rel="stylesheet" id="editor-buttons-css" href="https://hdquiz.local/wp-includes/css/editor.min.css?ver=6.6.2" media="all" />
		<div id="wp-hdq_result_content_result_${countOutcomes}-editor-tools" class="wp-editor-tools hide-if-no-js">
			<div id="wp-hdq_result_content_result_${countOutcomes}-media-buttons" class="wp-media-buttons">
				<button type="button" id="insert-media-button" class="button insert-media add_media" data-editor="hdq_result_content_result_${countOutcomes}"><span class="wp-media-buttons-icon"></span> Add Media</button>
			</div>
			<div class="wp-editor-tabs">
				<button type="button" id="hdq_result_content_result_${countOutcomes}-tmce" class="wp-switch-editor switch-tmce" data-wp-editor-id="hdq_result_content_result_${countOutcomes}">Visual</button>
				<button type="button" id="hdq_result_content_result_${countOutcomes}-html" class="wp-switch-editor switch-html" data-wp-editor-id="hdq_result_content_result_${countOutcomes}">Text</button>
			</div>
		</div>
		<div id="wp-hdq_result_content_result_${countOutcomes}-editor-container" class="wp-editor-container">
			<div id="qt_hdq_result_content_result_${countOutcomes}_toolbar" class="quicktags-toolbar hide-if-no-js"></div>
			<textarea
				class="hd_input hd_editor_input wp-editor-area"
				style="height: 240px"
				autocomplete="off"
				cols="40"
				name="hdq_result_content_result_${countOutcomes}"
				id="hdq_result_content_result_${countOutcomes}"
				data-type="editor"
				data-lt-tmp-id="lt-8142"
				spellcheck="false"
				data-gramm="false"></textarea>
		</div>
	</div>
</div>`;
			document.getElementById("personality_results").insertAdjacentHTML("beforeend", html);
			HDQ.form.createEditors();
			self.personality.remove.init();
		},
	},
};
