import { self as fetcher } from "../fetcher.js";

export const self = {
	get: async function (action, d = null) {
		console.log("getting the dashboard");
		let data = await self.getData();
		await self.render(data);
		_hd.init();

		// allow ENTER on quiz name creation
		const saveEl = document.getElementById("hd_save");
		saveEl.setAttribute("tabindex", 0); // wpkses_post sanitize this out
		document.getElementById("hdq_quiz_name").addEventListener("keyup", function (ev) {
			if (ev.key === "Enter") {
				saveEl.click();
			}
		});

		// stop click events on shortcode
		const items = document.getElementsByClassName("hdq_quiz_item");
		for (let i = 0; i < items.length; i++) {
			items[i].addEventListener("click", function (ev) {
				if (ev.target.tagName == "CODE") {
					ev.preventDefault();
				}
			});
		}
	},
	getData: async function () {
		let data = {};
		return await fetcher("hdq_get_view_dashboard", data);
	},
	render: async function (data) {
		document.getElementById("hdq_content").innerHTML = data.html;
		document.getElementById("hdq_content").scrollTo(0, 0);
		document.getElementById("hdq_loading").classList.remove("active");
	},
};
