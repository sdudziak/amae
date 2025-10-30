import { views } from "./views/index.js";

export const router = {
	views: views,
	init: async function () {
		await router.getView();
		window.addEventListener("hashchange", async function () {
			router.getView();
		});
	},
	getHash: async function () {
		let hash = window.location.hash;
		if (hash.length == 0) {
			hash = "#/dashboard";
		}
		return hash;
	},
	getView: async function (d = null) {
		let hash = await router.getHash();
		hash = hash.replaceAll("#/", "");

		d = hash.split("/");
		d.shift();
		hash = hash.split("/")[0];
		if (views[hash]) {
			router.views[hash].get(hash, d);
		} else {
			console.error("HD Quiz: View " + hash + " could not be found");
		}

		const loader = document.getElementById("hdq_loading");
		loader.classList.add("active");

		return hash;
	},
};
