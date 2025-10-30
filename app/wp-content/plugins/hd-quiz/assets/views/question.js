import { self as fetcher } from "../fetcher.js";

export const self = {
	get: async function (action, d = null) {
		// d[0] == quiz_id, d[1] == question_id
		console.log("getting the question ID " + d[1]);
		let data = await self.getData(d);
		await self.render(data);
	},
	getData: async function (d) {
		let data = {
			quiz_id: parseInt(d[0]),
			question_id: parseInt(d[1]),
		};
		return await fetcher("hdq_get_view_question", data);
	},
	render: async function (data) {
		document.getElementById("hdq_content").innerHTML = data.html;
		document.getElementById("hdq_content").scrollTo(0, 0);
		document.getElementById("hdq_loading").classList.remove("active");

		_hd.init();
		HDQ.form.createEditors();
		HDQ.form.tabs();

		self.answers.data.question_id = data.question_id;
		self.answers.data.quiz_id = data.quiz_id;

		self.answers.init();

		const delete_el = document.getElementById("hd_delete_question");
		if (!delete_el) {
			return;
		}
		delete_el.addEventListener("click", async function () {
			const message = `You are about to delete this question. Continue?`; // HDQ_LOCALIZE
			if (confirm(message)) {
				deleteQuestion(this);
			}
		});

		async function deleteQuestion(el) {
			let data = {
				quiz_id: parseInt(el.getAttribute("data-quiz")),
				question_id: parseInt(el.getAttribute("data-id")),
			};
			let res = await fetcher("hdq_delete_question", data);
			HDQ.router.views.quiz.get(res.action.data, res.action.data2);

			let state = {
				quiz_id: res.action.data2,
			};
			history.pushState(state, "");
		}
	},
	update: function (question_id) {
		document.getElementById("question_id").value = parseInt(question_id[0]);
		document.getElementById("hd_delete_question").setAttribute("data-id", parseInt(question_id[0]));
	},
	answers: {
		data: {
			answers: [],
			question_id: 0,
			quiz_id: 0,
			defaults: {
				value: "",
				image: "",
				imageURL: "",
				selected: "",
				// set default fields here
			},
		},
		init: function () {
			self.answers.onChangeQuestionType();
			self.fieldAnswers.init();
		},
		onChangeQuestionType: function () {
			// when changing question type, get template data
			const questionTypeEl = document.getElementById("question_type");
			if (!questionTypeEl) {
				return;
			}
			questionTypeEl.addEventListener("change", async function () {
				self.answers.changeQuestionType(this);
			});

			setTimeout(function () {
				if (questionTypeEl.options[0].selected) {
					questionTypeEl.options[1].selected = true;
				}
				let event = new Event("change");
				questionTypeEl.dispatchEvent(event);
			}, 10);
		},
		changeQuestionType: async function (el) {
			document.getElementById("hdq_loading").classList.add("active");
			const questionTypeEl = document.getElementById("question_type");
			let type = el.value;

			// don't allow "no selection"
			if (type === "" || type === null) {
				questionTypeEl.options[1].selected = true;
				let event = new Event("change");
				questionTypeEl.dispatchEvent(event);
				return;
			}

			let data = {
				question_type: type,
				question_id: self.answers.data.question_id,
				quiz_id: self.answers.data.quiz_id,
			};

			// store current answer data
			self.answers.data.answers = await self.answers.getAnswerData();

			// update with new question type content
			data = await fetcher("hdq_get_question_type", data);
			if (data.status && data.status === "success") {
				document.getElementById("question_answers").innerHTML = data.html;
				if (data.action && data.action.name) {
					console.log("running action " + data.action.name);
					HDQ[data.action.name]();
				}

				// attempt to set answer data from stored
				self.answers.setAnswerData(self.answers);
				document.getElementById("hdq_loading").classList.remove("active");
			} else {
				// something went wrong
				console.log(data);
			}
		},
		getAnswerData: async function (field = document.getElementById("question_answers")) {
			const rows = field.getElementsByClassName("hdq_answer_row");
			if (rows.length === 0) {
				return [[], false];
			}

			let answers = [];
			for (let i = 0; i < rows.length; i++) {
				let answer = JSON.parse(JSON.stringify(self.answers.data.defaults));
				const items = rows[i].getElementsByClassName("hdq_answer_item_input");
				for (let ii = 0; ii < items.length; ii++) {
					let type = items[ii].getAttribute("data-answer-type");
					if (items[ii].getAttribute("type") == "checkbox") {
						if (items[ii].checked) {
							answer[type] = "yes";
						}
					} else {
						if (items[ii].getAttribute("data-value")) {
							answer[type] = items[ii].getAttribute("data-value");
						} else {
							answer[type] = items[ii].value;
						}
					}
				}
				answers.push(answer);
			}

			let answers_clean = [];
			for (let i = 0; i < answers.length; i++) {
				if (answers[i].value && answers[i].value !== "") {
					answers_clean.push(answers[i]);
				}
			}
			return [answers_clean, true];
		},
		setAnswerData: async function () {
			let answers = self.answers.data.answers[0];
			if (answers.length === 0) {
				return;
			}

			const rows = document.getElementsByClassName("hdq_answer_row");
			for (let i = 0; i < rows.length; i++) {
				const items = rows[i].getElementsByClassName("hdq_answer_item_input");
				for (let ii = 0; ii < items.length; ii++) {
					let type = items[ii].getAttribute("data-answer-type");
					if (answers[i] && answers[i][type]) {
						if (items[ii].getAttribute("type") == "checkbox") {
							items[ii].checked = true;
						} else {
							if (items[ii].getAttribute("data-value")) {
								items[ii].setAttribute("data-value", answers[i][type]);
								if (type === "image") {
									items[ii].innerHTML = "";
									items[ii].insertAdjacentElement("beforeend", answers[i].imageURL);
									items[ii].nextElementSibling.classList.add("active");
								}
							} else {
								items[ii].value = answers[i][type];
							}
						}
					}
				}
			}
		},
	},
	fieldAnswers: {
		init: function () {
			_hd.validate.field.hdq_field_answers = self.fieldAnswers.validate; // set HDFields validation function
		},
		validate: async function (field) {
			let valid = true;
			let dataArr = await self.answers.getAnswerData(field);
			let data = dataArr[0];
			if (dataArr[1]) {
				if (data.length == 0) {
					valid = false;
				}
			}

			let v = {
				value: data,
				status: valid,
			};
			return v;
		},
	},
};
