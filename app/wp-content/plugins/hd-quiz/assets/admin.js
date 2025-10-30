import { router } from "./router.js";
import { form } from "./form.js";
import { sort } from "./sort.js";

const HDQ = {
	init: async function () {
		console.log("HD Quiz v" + HDQ_VERSION + " init");

		async function render() {
			await HDQ.router.init();
		}
		await render();
		HDQ.form.createEditors();
	},
	router: router,
	form: form,
	sort: sort,
	images: function () {
		_hd.images.init();
	},
	reload: function (el) {
		let l = el.getAttribute("href");
		let c = window.location.href;
		if (c.includes(l)) {
			window.location.reload();
		}
	},
};
HDQ.init();

window.HDQ = HDQ;
