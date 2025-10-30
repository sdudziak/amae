const HDQ = {
	init: function () {
		console.log("HD Quiz Importer init");
		HDQ.upload.init();
	},
	data: [],
	i: 0,
	upload: {
		init: function () {
			document.getElementById("hdq_csv_upload_form").addEventListener("submit", async function (ev) {
				ev.preventDefault();
				this.disabled = true;
				const formdata = new FormData();
				formdata.append("action", "hdq_accept_csv");
				const nonce = document.getElementById("hdq_tools_nonce").value;
				formdata.append("hdq_tools_nonce", nonce);

				const file = document.getElementById("hdq_csv_file_upload");
				formdata.append("hdq_csv_file_upload", file.files[0]);

				let res = await fetch(ajaxurl, {
					method: "POST",
					credentials: "same-origin",
					body: formdata,
				});
				res = await res.json();
				const el = document.getElementById("hdq_csv_upload_form_wrapper");
				if (!res.status) {
					alert("Unknown error uploading your file");
				} else {
					if (res.status === "fail") {
						el.innerHTML = res.message;
					} else {
						el.style.display = "none";
						document.getElementById("hdq_wrapper").insertAdjacentHTML("beforeend", `<div id = "hdq_logs"><div class = "hdq_log">${res.message}</div></div>`);
						HDQ.data = res.data;
						HDQ.next();
					}
				}
			});
		},
	},
	next: async function () {
		let question = HDQ.data[HDQ.i];

		const formdata = new FormData();
		formdata.append("action", "hdq_csv_import_question");
		const nonce = document.getElementById("hdq_tools_nonce").value;
		formdata.append("hdq_tools_nonce", nonce);
		formdata.append("question", JSON.stringify(question));

		let res = await fetch(ajaxurl, {
			method: "POST",
			credentials: "same-origin",
			body: formdata,
		});
		res = await res.json();
		if (res.status == "success") {
			const el = document.getElementById("hdq_logs");
			HDQ.i = parseInt(HDQ.i) + 1;
			el.insertAdjacentHTML("afterbegin", `<div class = "hdq_log">Imported ${HDQ.i} / ${HDQ.data.length}</div>`);
			if (HDQ.i + 1 > HDQ.data.length) {
				el.insertAdjacentHTML("afterbegin", `<div class = "hdq_log" style = "color:darkgreen">Import complete! It is now safe to leave this page</div>`);
				return;
			}
			HDQ.next();
		} else {
			el.insertAdjacentHTML("afterbegin", `<div class = "hdq_log" style = "color:darkred">There was an error importing this question. Check browser console log (F12) for more details</div>`);
		}
	},
};
HDQ.init();
