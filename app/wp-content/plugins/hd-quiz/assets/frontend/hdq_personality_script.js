// hdq_kb events
const HDQ = {
	VARS: {},
	init: async function () {
		console.log("HD Quiz v" + HDQ_VERSION + " init [personality]");
		HDQ.VARS = HDQ_DATA;

		HDQ.el = document.getElementsByClassName("hdq_quiz_wrapper")[0];

		let init_actions = HDQ.VARS.hdq_init;
		if (typeof init_actions != "undefined" && init_actions != null) {
			for (let i = 0; i < init_actions.length; i++) {
				console.log(init_actions[i]);
				if (typeof window[init_actions[i]] === "function") {
					await window[init_actions[i]]();
				}
			}
		}

		HDQ.answers.personality_multiple_choice_image = HDQ.answers.personality_multiple_choice_text;
		HDQ.answers.init();

		HDQ.el.getElementsByClassName("hdq_finsh_button")[0].addEventListener("click", function () {
			HDQ.submit();
		});

		HDQ.paginate.init();
		HDQ.kb();
	},
	kb: function () {
		const items = document.getElementsByClassName("hdq_kb");
		for (let i = 0; i < items.length; i++) {
			items[i].addEventListener("keyup", function (ev) {
				if (ev.which == 32) {
					this.click();
				}
			});
		}
	},
	paginate: {
		init: function () {
			// next buttons
			const items = HDQ.el.getElementsByClassName("hdq_jPaginate_button");
			for (let i = 0; i < items.length; i++) {
				items[i].addEventListener("click", function () {
					HDQ.paginate.next(this);
				});
			}
			HDQ.el.getElementsByClassName("hdq_finish")[0].classList.remove("hdq_hidden");

			if (HDQ.VARS.quiz.timer != "" && parseInt(HDQ.VARS.quiz.timer) >= 3 && HDQ.VARS.quiz.timer_per_question === "yes") {
				return; // don't allow previous buttons if timer-per question
			}

			// prev buttons
			const prev_buttons = HDQ.el.getElementsByClassName("hdq_prev_button");
			if (prev_buttons.length == 0) {
				return;
			}

			prev_buttons[0].remove(); // remove the first prev button

			for (let i = 0; i < prev_buttons.length; i++) {
				prev_buttons[i].classList.remove("hdq_hidden");
				prev_buttons[i].addEventListener("click", function () {
					HDQ.paginate.prev(this);
				});
			}
		},
		grid_classes: ["layout_left", "layout_left_full", "layout_right", "layout_right_full"], // css grid classes Quiz Styler addon
		getDisplay(question) {
			const class_styles = HDQ.paginate.grid_classes;
			let style = "block";
			for (let i = 0; i < class_styles.length; i++) {
				if (question.classList.contains(class_styles[i])) {
					style = "grid";
					break;
				}
			}
			return style;
		},
		removeAll: function () {
			const items = HDQ.el.getElementsByClassName("hdq_jPaginate");
			for (let i = 0; i < items.length; i++) {
				items[i].classList.add("hdq_hidden");
			}
		},
		next: function (el) {
			if (!el) {
				return;
			}
			const questions = HDQ.el.getElementsByClassName("hdq_question");

			// hide all questions before pagination button
			for (let i = 0; i < questions.length; i++) {
				if (questions[i].compareDocumentPosition(el) === 4) {
					questions[i].classList.add("hdq_hidden");
					if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
						questions[i].nextElementSibling.style.display = "none";
					}
				} else {
					break;
				}
			}

			// show all questions after this, but before the next jPaginate
			// start by finding the next pagination section
			let next_paginate = false;
			const paginate_sections = HDQ.el.getElementsByClassName("hdq_jPaginate");
			for (let i = 0; i < paginate_sections.length; i++) {
				if (paginate_sections[i] === el.parentElement) {
					if (i + 1 <= paginate_sections.length) {
						next_paginate = paginate_sections[i + 1];
					}
				}
			}
			if (!next_paginate) {
				console.warn("HD QUIZ: Unable to find next pagination section");
				return;
			}
			next_paginate.style.display = "block";

			// Show the correct questions
			for (let i = 0; i < questions.length; i++) {
				if (questions[i].compareDocumentPosition(el) === 2 && questions[i].compareDocumentPosition(next_paginate) === 4) {
					questions[i].classList.remove("hdq_hidden");
					let display_style = HDQ.paginate.getDisplay(questions[i]);
					questions[i].style.display = display_style;
					if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
						questions[i].nextElementSibling.style.display = "block";
					}
				}
			}

			el.parentElement.style.display = "none"; // hide the clicked on pagination section

			HDQ.el.getElementsByClassName("hdq_offset_div")[0].scrollIntoView({
				behavior: "smooth",
				block: "start",
				inline: "nearest",
			});
		},
		prev: function (el) {
			if (!el) {
				return;
			}

			// start by hiding all questions
			const questions = HDQ.el.getElementsByClassName("hdq_question");
			for (let i = 0; i < questions.length; i++) {
				questions[i].classList.add("hdq_hidden");
				if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
					questions[i].nextElementSibling.style.display = "none";
				}
			}

			// find the previous jPagination
			const jPaginate = HDQ.el.getElementsByClassName("hdq_jPaginate");

			// store indexes
			let paginate = {
				start: null,
				end: null,
			};

			for (let i = 0; i < jPaginate.length; i++) {
				let children = jPaginate[i].children;
				for (let ii = 0; ii < children.length; ii++) {
					if (children[ii] === el) {
						if (i > 0) {
							paginate.end = i - 1;
						}
						if (i > 1) {
							paginate.start = i - 2;
						}
						break;
					}
				}
			}

			if (paginate.start === null) {
				// from first question to paginate.end
				for (let i = 0; i < questions.length; i++) {
					if (questions[i].compareDocumentPosition(jPaginate[paginate.end]) === 4) {
						questions[i].classList.remove("hdq_hidden");
						let display_style = HDQ.paginate.getDisplay(questions[i]);
						questions[i].style.display = display_style;
						if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
							questions[i].nextElementSibling.style.display = "block";
						}
					}
				}
				jPaginate[paginate.end].style.display = "block";
			} else {
				for (let i = 0; i < questions.length; i++) {
					if (questions[i].compareDocumentPosition(jPaginate[paginate.start]) === 2 && questions[i].compareDocumentPosition(jPaginate[paginate.end]) === 4) {
						questions[i].classList.remove("hdq_hidden");
						let display_style = HDQ.paginate.getDisplay(questions[i]);
						questions[i].style.display = display_style;
						if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
							questions[i].nextElementSibling.style.display = "block";
						}
					}
				}
				jPaginate[paginate.end].style.display = "block";
			}
			el.parentElement.style.display = "none";

			HDQ.el.getElementsByClassName("hdq_offset_div")[0].scrollIntoView({
				behavior: "smooth",
				block: "start",
				inline: "nearest",
			});
		},
	},
	questions: {
		jcount: 0, // used to count paginations
		mark: function (question, highlight = true) {
			const type = question.getAttribute("data-type");
			let score = HDQ.answers[type].mark(question, highlight);

			if (!highlight) {
				return score;
			}

			HDQ.questions.disable(question);

			// extra content area
			const extra_content_el = question.getElementsByClassName("hdq_question_after_text");
			if (extra_content_el.length > 0) {
				if (score[0] < 1 || HDQ.VARS.quiz.force_show_extra_content === "yes") {
					extra_content_el[0].style.display = "block";
				}
			}

			// remove submit buttons if exist
			const submit_answers_el = question.getElementsByClassName("hdq_submit_question_answers");
			while (submit_answers_el.length > 0) {
				submit_answers_el[0].remove();
			}
			return score;
		},
		showAll: function () {
			const questions = HDQ.el.getElementsByClassName("hdq_question");
			for (let i = 0; i < questions.length; i++) {
				questions[i].classList.remove("hdq_active_question");
				questions[i].classList.remove("hdq_hidden");
			}
		},
		hideAll: function () {
			const questions = HDQ.el.getElementsByClassName("hdq_question");
			for (let i = 0; i < questions.length; i++) {
				questions[i].classList.add("hdq_hidden");
			}
		},
		disableAll: function () {
			const questions = document.getElementsByClassName("hdq_question");
			for (let i = 0; i < questions.length; i++) {
				HDQ.questions.disable(questions[i]);
			}
		},
		disable: function (question) {
			const answers = question.getElementsByClassName("hdq_option");
			for (let i = 0; i < answers.length; i++) {
				answers[i].disabled = true;
			}
		},
		enable: function (question) {
			const answers = question.getElementsByClassName("hdq_option");
			for (let i = 0; i < answers.length; i++) {
				answers[i].disabled = false;
			}
		},
		next: function () {
			// set the active question. used for timer-per question
			let current_question = HDQ.el.getElementsByClassName("hdq_active_question");
			if (current_question.length == 0) {
				current_question = HDQ.el.getElementsByClassName("hdq_question");
			}
			current_question = current_question[0];

			if (!current_question.classList.contains("hdq_active_question")) {
				current_question.classList.add("hdq_active_question");
				if (current_question.getAttribute("data-type") === "question_as_title") {
					HDQ.questions.next();
				}
				return;
			}

			const questions = HDQ.el.getElementsByClassName("hdq_question");

			// get the next question
			let found = false;
			for (let i = 0; i < questions.length; i++) {
				if (questions[i] === current_question) {
					if (i + 1 <= questions.length) {
						found = questions[i + 1];
					}
				}
			}

			// if there is no next question, end quiz
			if (!found) {
				HDQ.submit();
				return;
			}

			current_question.classList.remove("hdq_active_question");
			found.classList.add("hdq_active_question");

			if (found.getAttribute("data-type") === "question_as_title") {
				HDQ.questions.next();
				return;
			}

			HDQ.questions.disableAll();
			HDQ.questions.enable(found);

			if (found.checkVisibility()) {
				found.scrollIntoView({
					behavior: "smooth",
					block: "center",
					inline: "nearest",
				});
			} else {
				// Must be hidden behind paginate
				const pagainte_button = HDQ.el.getElementsByClassName("hdq_next_button")[HDQ.questions.jcount];
				HDQ.paginate.next(pagainte_button);
				HDQ.questions.jcount++;
			}
			HDQ.timer.reset();
		},
	},
	answers: {
		init: function () {
			const questions = HDQ.el.getElementsByClassName("hdq_question");
			for (let i = 0; i < questions.length; i++) {
				let type = questions[i].getAttribute("data-type");
				if (HDQ.answers[type]) {
					HDQ.answers[type].init(questions[i]);
				} else {
					console.warn("Question type " + type + " does not exist");
				}
			}
		},
		personality_multiple_choice_text: {
			init: function (question) {
				const answers = question.getElementsByClassName("hdq_option");
				for (let i = 0; i < answers.length; i++) {
					answers[i].addEventListener("change", function () {
						HDQ.answers.personality_multiple_choice_text.select(question, this);
					});
				}
			},
			select: function (question, answer) {
				const answers = question.getElementsByClassName("hdq_option");
				for (let i = 0; i < answers.length; i++) {
					answers[i].checked = false;
				}
				answer.checked = true;
				if (HDQ.VARS.quiz.stop_answer_reselect === "yes") {
					HDQ.questions.disable(question);
				}
			},
			mark: function (question, highlight = true) {
				const answers = question.getElementsByClassName("hdq_option");
				let selected = {};
				for (let i = 0; i < answers.length; i++) {
					if (answers[i].checked) {
						let data = JSON.parse(answers[i].getAttribute("data-value"));
						for (let i = 0; i < data.length; i++) {
							if (!selected[data[i]]) {
								selected[data[i]] = 0;
							}
							selected[data[i]] = selected[data[i]] + 1;
						}
					}
				}
				return selected;
			},
		},
		personality_multiple_choice_image: null,
		question_as_title: {
			init: function (question) {},
			mark: function () {
				return null;
			},
		},
	},
	share: {
		init: function () {
			if (HDQ.VARS.settings.allow_social_media === "yes" && HDQ.VARS.quiz.share_quiz_results === "yes") {
				HDQ.share.twitter();
				HDQ.share.bluesky();
				HDQ.share.other();

				if (HDQ.VARS.settings.enhanced_facebook === "yes") {
					HDQ.share.facebook();
				}
			}
		},
		facebook: function () {
			let baseURL = window.location.origin + "/hd-quiz/share";
			baseURL += "?quiz_id=" + HDQ.VARS.quiz.quiz_id;
			baseURL += "&permalink=" + HDQ.VARS.quiz.permalink;
			baseURL += "&score=" + HDQ.VARS.hdq_score;
			baseURL += "&redirect=1";
			const el = document.getElementsByClassName("hdq_facebook");
			if (el.length === 0) {
				return;
			}
			el[0].setAttribute("href", "https://www.facebook.com/sharer/sharer.php?u=" + baseURL);
		},
		twitter: function () {
			let baseURL = "https://twitter.com/intent/tweet";
			let text = HDQ.VARS.settings.share_text_personality;
			let score = HDQ.VARS.hdq_score;
			text = text.replaceAll("%score%", score);
			text = text.replaceAll("%quiz%", HDQ.VARS.quiz_name);

			if (HDQ.VARS.twitter != "") {
				baseURL += "?screen_name=" + HDQ.VARS.settings.twitter_handle;
			} else {
				baseURL += "?";
			}
			text = "&text=" + encodeURI(text);
			let url = "&url=" + encodeURI(HDQ.VARS.quiz.permalink);
			let hashtags = "&hashtags=hdquiz";
			let shareLink = baseURL + text + url + hashtags;
			const el = HDQ.el.getElementsByClassName("hdq_twitter");
			if (el.length === 0) {
				return;
			}
			el[0].setAttribute("href", shareLink);
		},
		bluesky: function () {
			let baseURL = "https://bsky.app/intent/compose";
			let text = HDQ.VARS.settings.share_text_personality;
			let score = HDQ.VARS.hdq_score;
			text = text.replaceAll("%score%", score);
			text = text.replaceAll("%quiz%", HDQ.VARS.quiz_name);
			text = text + " " + encodeURI(HDQ.VARS.quiz.permalink) + " #hdquiz";
			const shareLink = baseURL + "?text=" + encodeURI(text);
			const el = HDQ.el.getElementsByClassName("hdq_bluesky");
			if (el.length === 0) {
				return;
			}
			el[0].setAttribute("href", shareLink);
		},
		other: function () {
			// for the most part, only available on movile devices
			let el = HDQ.el.getElementsByClassName("hdq_share_other");
			if (el.length === 0) {
				return;
			}
			el = el[0];
			try {
				if (!navigator.canShare) {
					el.remove();
				}
			} catch (err) {
				el.remove();
			}

			el.addEventListener("click", async function () {
				let text = HDQ.VARS.settings.share_text_personality;
				let score = HDQ.VARS.hdq_score;
				text = text.replaceAll("%score%", score);
				text = text.replaceAll("%quiz%", HDQ.VARS.quiz_name);

				const data = {
					title: "HD Quiz",
					text: text,
					url: HDQ.VARS.quiz.permalink,
				};

				try {
					await navigator.share(data);
				} catch (err) {
					console.warn(err);
				}
			});
		},
	},
	submitAction: async function (action) {
		console.log("onSumbit action: " + action);

		let data = {
			quizID: HDQ.VARS.quiz.quiz_id,
			score: HDQ.VARS.hdq_score,
		};

		// if this is also a JS function, store data
		if (typeof window[action] !== "undefined") {
			let extra = {};
			data[action] = await window[action]();
			data["extra"] = data[action]; // for legacy
		}

		console.log(data);

		const formdata = new FormData();
		formdata.append("action", action);
		formdata.append("data", JSON.stringify(data));

		let res = await fetch(HDQ.VARS.quiz.ajax_url, {
			method: "POST",
			credentials: "same-origin",
			body: formdata,
		});
		res = await res.text();
		console.log(res);
	},
	redirect: {
		init: async function () {
			if (!HDQ.VARS.quiz.quiz_redirect_url || HDQ.VARS.quiz.quiz_redirect_url == "") {
				return;
			}
			HDQ.redirect.delay();
		},
		delay: function () {
			let timeout = 0;
			timeout = parseInt(HDQ.VARS.quiz.quiz_redirect_delay) + 1;
			timeout = timeout * 1000;
			setTimeout(() => {
				window.location.href = HDQ.VARS.quiz.quiz_redirect_url;
			}, timeout);
		},
	},
	submit: async function () {
		const questions = HDQ.el.getElementsByClassName("hdq_question");

		if (HDQ.VARS.quiz.force_answers === "yes") {
			for (let i = 0; i < questions.length; i++) {
				let s = HDQ.questions.mark(questions[i], false);
				if (s !== null) {
					s = Object.keys(s);
				} else {
					s = [null];
				}
				if (s.length == 0) {
					questions[i].classList.remove("hdq_answer_required");
					if (!s[2]) {
						HDQ.paginate.removeAll();
						HDQ.questions.showAll();

						console.warn("You must fill out all questions");
						questions[i].scrollIntoView({
							behavior: "smooth",
							block: "center",
							inline: "nearest",
						});
						questions[i].classList.add("hdq_answer_required");

						HDQ.el.getElementsByClassName("hdq_finish")[0].classList.remove("hdq_hidden");
						return;
					}
				}
			}
		}

		let results = {};
		for (let i = 0; i < questions.length; i++) {
			let s = HDQ.questions.mark(questions[i]);
			if (s !== null) {
				for (const [k, v] of Object.entries(s)) {
					let kk = k.replace("selected_", "");
					if (!results[kk]) {
						results[kk] = 0;
					}
					results[kk] = results[kk] + v;
				}
			}
		}

		// get the final answer
		let score = 0;
		for (const [k, v] of Object.entries(results)) {
			if (v > score) {
				score = v;
			}
		}

		for (const [k, v] of Object.entries(results)) {
			if (v == score) {
				score = k;
				break;
			}
		}
		console.log(score);

		HDQ.paginate.removeAll();

		if (HDQ.VARS.quiz.hide_questions_after_completion === "yes") {
			HDQ.questions.hideAll();
		} else {
			HDQ.questions.showAll();
		}

		HDQ.el.getElementsByClassName("hdq_finsh_button")[0].remove();
		const results_el = HDQ.el.getElementsByClassName("hdq_results_wrapper")[0];
		if (score != 0) {
			document.getElementById("hdq_results_" + score).style.display = "block";
			HDQ.VARS.hdq_score = document.getElementById("hdq_results_" + score).getElementsByClassName("hdq_result")[0].innerText;
		} else {
			HDQ.el.getElementsByClassName("hdq_result_personality")[0].style.display = "block"; // just show the first
			HDQ.VARS.hdq_score = HDQ.el.getElementsByClassName("hdq_result_personality")[0].getElementsByClassName("hdq_result")[0].innerText;
		}
		results_el.style.display = "block";
		HDQ.el.getElementsByClassName("hdq_loading_bar")[0].classList.add("hdq_animate");

		HDQ.share.init();

		setTimeout(function () {
			results_el.scrollIntoView({
				behavior: "smooth",
				block: "center",
				inline: "nearest",
			});
		}, 1200);

		for (let i = 0; i < HDQ.VARS.hdq_submit.length; i++) {
			await HDQ.submitAction(HDQ.VARS.hdq_submit[i]);
		}

		HDQ.redirect.init();
	},
};

(function () {
	const el = document.getElementsByClassName("hdq_quiz");
	if (el.length === 1) {
		HDQ.init();
	} else {
		console.warn("HD Quiz: Multiple quizzes found on this page");
		const hd_warning = `<div class = "hdq_multiple_quizzes_warning"><p>HD Quiz: You have multiple quizzes running on this page. <br/>Due to the complexity of quizzes, only one quiz can be on a page at a time. Please place quizzes on seprate pages.</p></div>`;
		for (let i = 0; i < el.length; i++) {
			el[i].insertAdjacentHTML("beforebegin", hd_warning);
		}
	}
})();
