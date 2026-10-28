import pathlib
R = pathlib.Path(__file__).parent
bs = R / "vendor/bs/dist/css"
strip = lambda s: s.replace("/*# sourceMappingURL=bootstrap.min.css.map */", "").replace("/*# sourceMappingURL=bootstrap.rtl.min.css.map */", "")
js = "\n".join((R / "src" / f).read_text() for f in ["i18n.js", "data.js", "core.js", "views-main.js", "views-more.js"])
html = (R / "src/index.template.html").read_text()
html = html.replace("/*BS_LTR*/", strip((bs / "bootstrap.min.css").read_text())).replace("/*BS_RTL*/", strip((bs / "bootstrap.rtl.min.css").read_text()))
html = html.replace("/*APP_CSS*/", (R / "src/app.css").read_text()).replace("/*APP_JS*/", js)
(R / "dist").mkdir(exist_ok=True)
(R / "dist/paradigm-crm.html").write_text(html)
(R / "dist/app.js").write_text(js)
print(len(html) // 1024, "KB")
