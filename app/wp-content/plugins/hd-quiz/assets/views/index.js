import { self as dashboard } from "./dashboard.js";
import { self as quiz } from "./quiz.js";
import { self as question } from "./question.js";

export const views = {
	dashboard: dashboard,
	quiz: quiz,
	question: question,
	reload: {
		get: function (data) {
			location.reload(true);
		},
	},
};
