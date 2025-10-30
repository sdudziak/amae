export const self = async function (action = "", data = {}) {
	let url = await createUrl(action, data);
	let admin_ajax = null;
	if (ajaxurl) {
		admin_ajax = ajaxurl;
	}
	if (ajaxurl === null) {
		console.error("No ajax url found");
		return;
	}

	const formdata = new FormData();
	formdata.append("action", action);

	for (const [key, value] of Object.entries(data)) {
		formdata.append(key, value);
	}
	const nonce = document.getElementById("hd_wpnonce").value;
	formdata.append("HD_NONCE", nonce);
	let res = await fetch(admin_ajax, {
		method: "POST",
		credentials: "same-origin",
		body: formdata,
	});
	res = await res.json();
	return res;

	async function createUrl(action, data) {
		let url = "";
		for (const k in data) {
			url += "&" + k + "=" + data[k];
		}
		return `action=${action}` + url;
	}

	function create_error_message(data) {
		console.log(data);
	}
};
