const HDQ = {
	VARS: {},
	init: async function () {
		console.log("HD Quiz v" + HDQ_VERSION + " init [general]");
		HDQ.VARS = HDQ_DATA;

		HDQ.el = document.getElementsByClassName("hdq_quiz_wrapper")[0];
		HDQ.VARS.timer = {
			active: false,
			current: 0,
			total: 0,
		};

		let init_actions = HDQ.VARS.hdq_init;
		if (typeof init_actions != "undefined" && init_actions != null) {
			for (let i = 0; i < init_actions.length; i++) {
				console.log(init_actions[i]);
				if (typeof window[init_actions[i]] === "function") {
					await window[init_actions[i]]();
				}
			}
		}

		HDQ.answers.multiple_choice_image = HDQ.answers.multiple_choice_text;
		HDQ.answers.select_all_apply_image = HDQ.answers.select_all_apply_text;
		HDQ.answers.init(); // when an answer is selected

		const finish = HDQ.el.getElementsByClassName("hdq_finsh_button");
		if (finish.length > 0) {
			finish[0].addEventListener("click", function () {
				HDQ.submit();
			});
		}

		HDQ.paginate.init();
		HDQ.timer.init();
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
			if (HDQ.VARS.quiz.wp_pagination != "" && HDQ.VARS.quiz.wp_pagination > 0) {
				HDQ.paginate.wpPagination();
			}

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
		wpPagination: function () {
			// currentScore
			// totalQuestions
			const next_el = HDQ.el.getElementsByClassName("hdq_next_button");
			for (let i = 0; i < next_el.length; i++) {
				let href = next_el[i].getAttribute("href");
				if (href === null) {
					continue;
				}
				next_el[i].addEventListener("click", function (ev) {
					ev.preventDefault();
					if (this.classList.contains("hdq_disable")) {
						return;
					}
					this.classList.add("hdq_disable");
					this.innerText = "...";
					let current_score = parseInt(document.getElementById("hdq_current_score").value);
					let total_questions = parseInt(document.getElementById("hdq_total_questions").value);

					const questions = HDQ.el.getElementsByClassName("hdq_question");
					for (let i = 0; i < questions.length; i++) {
						let type = questions[i].getAttribute("data-type");
						if (type !== "question_as_title") {
							total_questions = total_questions + 1;
							let score = HDQ.questions.mark(questions[i]);
							current_score = parseInt(current_score) + parseInt(score[0]);
						}
					}
					let h = this.getAttribute("href");
					h = h + `?currentScore=${current_score}&totalQuestions=${total_questions}`;
					this.setAttribute("href", h);

					window.location.href = h;
				});
			}
		},
		grid_classes: ["layout_left", "layout_left_full", "layout_right", "layout_right_full"], // css grid classes Quiz Styler addon
		getDisplay() {
			let style = "block";
			for (let i = 0; i < HDQ.paginate.grid_classes.length; i++) {
				if (HDQ.el.getElementsByClassName("hdq_quiz")[0].classList.contains(HDQ.paginate.grid_classes[i])) {
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
					let display_style = HDQ.paginate.getDisplay();
					questions[i].style.display = display_style;
					if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
						questions[i].nextElementSibling.style.display = display_style;
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
						let display_style = HDQ.paginate.getDisplay();
						questions[i].style.display = display_style;
						if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
							questions[i].nextElementSibling.style.display = display_style;
						}
					}
				}
				jPaginate[paginate.end].style.display = "block";
			} else {
				for (let i = 0; i < questions.length; i++) {
					if (questions[i].compareDocumentPosition(jPaginate[paginate.start]) === 2 && questions[i].compareDocumentPosition(jPaginate[paginate.end]) === 4) {
						questions[i].classList.remove("hdq_hidden");
						let display_style = HDQ.paginate.getDisplay();
						questions[i].style.display = display_style;
						if (questions[i].nextElementSibling.classList.contains("hdq_adset_container")) {
							questions[i].nextElementSibling.style.display = display_style;
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
	timer: {
		interval: null, // the actual timer
		count: function () {
			// create human time and update timer element text
			const el = document.getElementsByClassName("hdq_timer")[0];
			let minutes = parseInt(HDQ.VARS.timer.current / 60);
			minutes = minutes < 10 ? "0" + minutes : minutes;
			let seconds = HDQ.VARS.timer.current % 60;
			seconds = seconds < 10 ? "0" + seconds : seconds;
			let t = minutes + ":" + seconds;
			el.innerText = t;
			if (HDQ.VARS.timer.current > 10 && HDQ.VARS.timer.current < 30) {
				el.classList.add("hdq_timer_warning");
			} else if (HDQ.VARS.timer.current <= 10) {
				el.classList.remove("hdq_timer_warning");
				el.classList.add("hdq_timer_danger");
			}
			HDQ.VARS.timer.current = HDQ.VARS.timer.current - 1;
		},
		init: function () {
			if (typeof HDQ.VARS.quiz.timer === "undefined" || HDQ.VARS.quiz.timer == "" || parseInt(HDQ.VARS.quiz.timer) < 3) {
				return; // force minimum 3 second timer
			}
			let start_button = HDQ.el.getElementsByClassName("hdq_quiz_start");
			if (start_button.length > 0) {
				start_button[0].addEventListener("click", function () {
					this.remove();
					HDQ.timer.start();
				});
			} else {
				HDQ.timer.start(); // needed for users with timer + ads so that ads don't start hidden
			}
		},
		start: function () {
			// Add the timer to the page
			const html = `<div class = "hdq_timer"></div>`;
			document.body.insertAdjacentHTML("beforeend", html);

			HDQ.VARS.timer = {
				active: true,
				total: HDQ.VARS.quiz.timer,
				current: HDQ.VARS.quiz.timer,
			};

			// give dom time to paint
			setTimeout(function () {
				HDQ.timer.count();
			}, 50);

			// Show the quiz
			const quiz_el = HDQ.el.getElementsByClassName("hdq_quiz")[0];
			// if first question had paginate enabled, auto click
			if (HDQ.el.getElementsByClassName("hdq_quiz")[0].children[1].classList.contains("hdq_jPaginate")) {
				HDQ.el.getElementsByClassName("hdq_quiz")[0].children[1].getElementsByClassName("hdq_next_button")[0].click();
			}
			quiz_el.classList.remove("hdq_hidden");
			quiz_el.style.display = "block";

			if (HDQ.VARS.quiz.timer_per_question === "yes") {
				HDQ.paginate.removeAll();
				HDQ.questions.next();
				HDQ.timer.interval = setInterval(function () {
					HDQ.timer.question();
				}, 1000);
			} else {
				HDQ.timer.interval = setInterval(function () {
					HDQ.timer.quiz();
				}, 1000);
			}
		},
		quiz: function () {
			if (HDQ.VARS.timer.current > 0 && HDQ.VARS.timer.active == true) {
				HDQ.timer.count();
				return;
			}
			HDQ.timer.end(true);
		},
		question: function () {
			if (HDQ.VARS.timer.current > 0 && HDQ.VARS.timer.active == true) {
				HDQ.timer.count();
				return;
			}
			HDQ.timer.reset(true);
		},
		reset: function (next = false) {
			const el = document.getElementsByClassName("hdq_timer")[0];
			el.innerText = "0";
			el.classList = "hdq_timer";
			HDQ.VARS.timer.current = HDQ.VARS.timer.total;

			if (next) {
				HDQ.questions.next();
			}
		},
		end: function (submit = false) {
			const el = document.getElementsByClassName("hdq_timer")[0];
			el.classList = "hdq_timer hdq_timer_complete";
			HDQ.VARS.timer.active = false;
			clearInterval(HDQ.timer.interval);
			if (submit) {
				// quiz ended before user completed
				el.innerText = "0";
				HDQ.submit();
			}
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
				if (typeof HDQ_SRP !== "undefined") {
					if (HDQ_SRP.VARS.position === "before_results") {
						HDQ.timer.end(false);
						current_question.classList.remove("hdq_active_question");
						HDQ.questions.disableAll();
						document.getElementById("hdq_srp_form").style.display = "block";
						return;
					}
				}
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
		multiple_choice_text: {
			init: function (question) {
				const answers = question.getElementsByClassName("hdq_option");
				for (let i = 0; i < answers.length; i++) {
					answers[i].addEventListener("change", function () {
						HDQ.answers.multiple_choice_text.select(question, this);
					});
				}
			},
			select: function (question, answer) {
				const answers = question.getElementsByClassName("hdq_option");
				for (let i = 0; i < answers.length; i++) {
					answers[i].checked = false;
				}
				answer.checked = true;
				if (HDQ.VARS.quiz.immediately_mark_answers === "yes") {
					HDQ.questions.mark(question);
				}

				if (HDQ.VARS.quiz.stop_answer_reselect === "yes") {
					HDQ.questions.disable(question);
				}

				if (HDQ.VARS.timer.active && HDQ.VARS.quiz.timer_per_question === "yes") {
					HDQ.questions.next();
				}
			},
			mark: function (question, highlight = true) {
				let score = 0;
				let answered = false;
				const answers = question.getElementsByClassName("hdq_option");
				for (let i = 0; i < answers.length; i++) {
					if (answers[i].checked) {
						score = answers[i].value;
						answered = true;
						break;
					}
				}

				if (highlight) {
					if (HDQ.VARS.quiz.mark_questions === "yes") {
						for (let i = 0; i < answers.length; i++) {
							if (answers[i].checked) {
								if (parseInt(answers[i].value) > 0) {
									answers[i].parentElement.parentElement.parentElement.classList.add("hdq_correct");
								}
								if (parseInt(answers[i].value) == 0) {
									answers[i].parentElement.parentElement.parentElement.classList.add("hdq_wrong");
								}
							} else {
								if (HDQ.VARS.quiz.mark_answers === "yes") {
									if (parseInt(answers[i].value) > 0) {
										answers[i].parentElement.parentElement.parentElement.classList.add("hdq_correct_not_selected");
									}
								}
							}
						}
					}
				}
				return [score, 1, answered];
			},
		},
		multiple_choice_image: null,
		select_all_apply_text: {
			init: function (question) {
				const answers = question.getElementsByClassName("hdq_option");
				for (let i = 0; i < answers.length; i++) {
					answers[i].addEventListener("change", function () {
						HDQ.answers.select_all_apply_text.select(question, this);
					});
				}
			},
			select: function (question, answer) {
				if (HDQ.VARS.quiz.stop_answer_reselect === "yes") {
					answer.disabled = true;
				}

				if (HDQ.VARS.quiz.immediately_mark_answers === "yes") {
					// we actually don't want to mark right away since multiple selections can be made
					// we only want to check when the current answer is incorrect
					if (answer.checked && parseInt(answer.value) < 1) {
						HDQ.questions.mark(question);
					} else {
						const submit_answers_el = question.getElementsByClassName("hdq_submit_question_answers");
						if (submit_answers_el.length > 0) {
							return;
						}
						const html = `<div class="hdq_submit_question_answers hdq_button hdq_kb" onclick = "HDQ.answers.select_all_apply_text.submit(this)" role="button" tabindex="0">${HDQ.VARS.settings.translate_submit}</div>`;
						question.insertAdjacentHTML("beforeend", html);
					}
				}

				if (HDQ.VARS.timer.active && HDQ.VARS.quiz.timer_per_question === "yes") {
					const submit_answers_el = question.getElementsByClassName("hdq_submit_question_answers");
					if (submit_answers_el.length > 0) {
						return;
					}
					const html = `<div class="hdq_submit_question_answers hdq_button hdq_kb" onclick = "HDQ.questions.next()" role="button" tabindex="0">${HDQ.VARS.settings.translate_submit}</div>`;
					question.insertAdjacentHTML("beforeend", html);
				}
			},
			mark: function (question, highlight = true) {
				// need to get all correct answers for the point
				let score = 1;
				let answered = false;

				const answers = question.getElementsByClassName("hdq_option");
				for (let i = 0; i < answers.length; i++) {
					// if answer is correct, but wasn't selected
					if (parseInt(answers[i].value) > 0 && !answers[i].checked) {
						score = 0;
					}
					if (answers[i].checked && parseInt(answers[i].value) == 0) {
						score = 0;
					}
					if (answers[i].checked) {
						answered = true;
					}
				}

				if (highlight) {
					if (HDQ.VARS.quiz.mark_questions === "yes") {
						for (let i = 0; i < answers.length; i++) {
							if (answers[i].checked) {
								if (parseInt(answers[i].value) == 0) {
									answers[i].parentElement.parentElement.parentElement.classList.add("hdq_wrong");
								} else {
									if (score === 0) {
										answers[i].parentElement.parentElement.parentElement.classList.add("hdq_correct_not_selected");
									} else {
										answers[i].parentElement.parentElement.parentElement.classList.add("hdq_correct");
									}
								}
							} else {
								if (HDQ.VARS.quiz.mark_questions === "yes") {
									if (parseInt(answers[i].value) > 0) {
										answers[i].parentElement.parentElement.parentElement.classList.add("hdq_correct_not_selected");
									}
								}
							}
						}
					}
				}
				return [score, 1, answered];
			},
			submit: function (el) {
				let question = el.parentElement;
				HDQ.questions.mark(question);
				el.remove();
			},
		},
		select_all_apply_image: null,
		text_based_answer: {
			init: function (question) {
				const answers = question.getElementsByClassName("hdq_option")[0];
				answers.addEventListener("change", function () {
					HDQ.answers.text_based_answer.select(question, this);
				});
			},
			select: function (question, answer) {
				if (HDQ.VARS.quiz.immediately_mark_answers === "yes") {
					HDQ.questions.mark(question);
				}

				if (HDQ.VARS.quiz.stop_answer_reselect === "yes") {
					HDQ.questions.disable(question);
				}

				if (HDQ.VARS.timer.active && HDQ.VARS.quiz.timer_per_question === "yes") {
					HDQ.questions.next();
				}
			},
			mark: function (question, highlight = true) {
				let score = 0;
				let answered = false;
				const answer = question.getElementsByClassName("hdq_option")[0];
				let value = answer.value.toLocaleUpperCase().trim();
				if (value !== "") {
					answered = true;
				}
				let answers = answer.getAttribute("data-answers");
				answers = decodeURIComponent(answers);
				answers = HDQ.answers.text_based_answer.decodeHtml(answers);
				answers = JSON.parse(answers);
				for (let i = 0; i < answers.length; i++) {
					answers[i] = answers[i].toLocaleUpperCase().trim();
				}
				score = HDQ.answers.text_based_answer.isCorrect(answers, value);

				if (highlight) {
					if (HDQ.VARS.quiz.mark_questions === "yes") {
						if (score == 0) {
							answer.parentElement.classList.add("hdq_wrong");
							if (HDQ.VARS.quiz.mark_answers === "yes") {
								answer.insertAdjacentHTML("afterend", `<span class = " hdq_correct_not_selected hdq_answered">${answers[0]}</div>`);
							}
						} else {
							answer.parentElement.classList.add("hdq_correct");
						}
						return [score, 1, answered];
					}
				}
				return [score, 1, answered];
			},
			isCorrect: function (answers, value) {
				let correct = 0;
				// check for stemming
				for (let i = 0; i < answers.length; i++) {
					if (answers[i][answers[i].length - 1] == "*") {
						const a = answers[i].slice(0, -1);
						if (a === value || value.startsWith(a)) {
							correct = 1;
						}
					}
				}
				if (answers.includes(value)) {
					correct = 1;
				}
				return correct;
			},
			decodeHtml: function (html) {
				var txt = document.createElement("textarea");
				txt.innerHTML = html;
				return txt.value;
			},
		},
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
			baseURL += "&score=" + HDQ.VARS.hdq_score + "," + HDQ.VARS.hdq_score[1];
			baseURL += "&redirect=1";
			const el = document.getElementsByClassName("hdq_facebook");
			if (el.length === 0) {
				return;
			}
			el[0].setAttribute("href", "https://www.facebook.com/sharer/sharer.php?u=" + baseURL);
		},
		twitter: function () {
			let baseURL = "https://twitter.com/intent/tweet";
			let text = HDQ.VARS.settings.share_text;
			let score = HDQ.VARS.hdq_score[0] + "/" + HDQ.VARS.hdq_score[1];
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
			let text = HDQ.VARS.settings.share_text;
			let score = HDQ.VARS.hdq_score[0] + "/" + HDQ.VARS.hdq_score[1];
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
			// for the most part, only available on mobile devices
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
				let text = HDQ.VARS.settings.share_text;
				let score = HDQ.VARS.hdq_score[0] + "/" + HDQ.VARS.hdq_score[1];
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
		if (HDQ.VARS.quiz.force_answers === "yes" && HDQ.VARS.quiz.timer_per_question !== "yes") {
			for (let i = 0; i < questions.length; i++) {
				let s = HDQ.questions.mark(questions[i], false);
				if (s !== null) {
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

		if (HDQ.VARS.timer.active) {
			HDQ.timer.end(false);
		}

		HDQ.paginate.removeAll();

		for (let i = 0; i < HDQ.VARS.hdq_before_submit.length; i++) {
			await HDQ.submitAction(HDQ.VARS.hdq_before_submit[i]);
		}

		let score = [0, 0]; // x / y
		for (let i = 0; i < questions.length; i++) {
			let s = HDQ.questions.mark(questions[i], true);
			if (s !== null) {
				score[0] = parseInt(score[0]) + parseInt(s[0]);
				score[1] = parseInt(score[1]) + parseInt(s[1]);
			}
		}

		if (HDQ.VARS.quiz.wp_pagination !== "" && HDQ.VARS.quiz.wp_pagination > 0) {
			let current_score = parseInt(document.getElementById("hdq_current_score").value);
			let total_questions = parseInt(document.getElementById("hdq_total_questions").value);
			score[0] = parseInt(score[0]) + parseInt(current_score);
			score[1] = parseInt(score[1]) + parseInt(total_questions);
		}

		if (typeof HDQ.VARS.hdq_score === "undefined") {
			HDQ.VARS.hdq_score = score;
		} else {
			score = HDQ.VARS.hdq_score;
		}

		let status = false;
		let percent = (score[0] / score[1]) * 100;
		if (percent >= HDQ.VARS.quiz.quiz_pass_percentage) {
			status = true;
		}

		HDQ.share.init();

		if (HDQ.VARS.quiz.hide_questions_after_completion === "yes") {
			HDQ.questions.hideAll();
		} else {
			HDQ.questions.showAll();
		}

		const results = HDQ.el.getElementsByClassName("hdq_results_wrapper")[0];
		results.style.display = "block";
		if (status) {
			results.getElementsByClassName("hdq_result_pass")[0].style.display = "block";
		} else {
			results.getElementsByClassName("hdq_result_fail")[0].style.display = "block";
		}
		const result_el = results.getElementsByClassName("hdq_result")[0];
		result_el.innerHTML = `${score[0]} / ${score[1]}  - <span class = "hdq_result_percent">${percent.toFixed(2).replace(/[.,]00$/, "")}%</span>`;

		HDQ.el.getElementsByClassName("hdq_loading_bar")[0].classList.add("hdq_animate");

		for (let i = 0; i < HDQ.VARS.hdq_submit.length; i++) {
			await HDQ.submitAction(HDQ.VARS.hdq_submit[i]);
		}

		setTimeout(function () {
			results.scrollIntoView({
				behavior: "smooth",
				block: "center",
				inline: "nearest",
			});
		}, 1200);

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
