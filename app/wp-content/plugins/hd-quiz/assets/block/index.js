(() => {
	"use strict";
	const e = window.wp.blocks,
		o = (window.wp.i18n, window.wp.blockEditor),
		t = window.wp.data,
		i = window.wp.components,
		l = window.ReactJSXRuntime,
		n = JSON.parse('{"UU":"hdq/quiz-block"}');
	(0, e.registerBlockType)(n.UU, {
		edit: function ({ attributes: e, setAttributes: n }) {
			const { quizId: s } = e;
			const c = (0, t.useSelect)((e) => e("core").getEntityRecords("taxonomy", "quiz", { per_page: -1, orderby: "name", order: "asc" }));
			let r = [{ label: "---", value: 0 }];
			if (null !== c) for (let e = 0; e < c.length; e++) r.push({ label: c[e].name, value: c[e].id });
			return (0, l.jsxs)("div", {
				...(0, o.useBlockProps)(),
				children: [
					(0, l.jsxs)("p", { children: [(0, l.jsx)("strong", { children: "HD Quiz:" }), " This block will be replaced with the selected quiz. Quizzes do not load while editing."] }),
					(0, l.jsx)(i.SelectControl, { label: "Quiz", value: s, options: r, onChange: (e) => n({ quizId: e }), __nextHasNoMarginBottom: !0 }),
				],
			});
		},
	});
})();
