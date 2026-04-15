;(function () {
  "use strict"

  var BASE_URL_TINYMCE = "/libs/editor"
  var SUFFIX = ".min"

  function normalizePlugins(p) {
    if (!p) return []
    if (Array.isArray(p)) return p.flatMap((s) => String(s).split(/\s+/)).filter(Boolean)
    return String(p).split(/\s+/).filter(Boolean)
  }

  function unionPlugins(a, b) {
    var set = new Set()
    normalizePlugins(a).forEach((x) => set.add(x))
    normalizePlugins(b).forEach((x) => set.add(x))
    return Array.from(set)
  }

  function dedupeToolbar(a, b) {
    const tok = (t) =>
      (t || "")
        .split("|")
        .map((s) => s.trim())
        .filter(Boolean)
    const norm = (s) => s.replace(/\s+/g, " ")
    const seen = new Set()
    const out = []
    for (const block of [...tok(a), ...tok(b)]) {
      const k = norm(block)
      if (!seen.has(k)) {
        seen.add(k)
        out.push(block)
      }
    }
    return out.join(" | ")
  }

  const TOOLBAR_POLICY = "base"
  const PLUGINS_POLICY = "union"

  var BASE = {
    height: 320,
    menubar: false,
    branding: false,
    statusbar: true,
    toolbar_mode: "wrap",
    language: "auto",
    media_live_embeds: false,

    plugins: [
      "advlist anchor autolink charmap code codesample directionality",
      "fullscreen emoticons image insertdatetime link lists media",
      "paste preview print pagebreak save searchreplace table template",
      "visualblocks wordcount",
    ].join(" "),

    toolbar: [
      "undo redo | styles | bold italic underline strikethrough |",
      "alignleft aligncenter alignright alignjustify | bullist numlist outdent indent |",
      "fontselect fontsizeselect forecolor backcolor removeformat |",
      "link image media table | pagebreak charmap emoticons |",
      "preview save print | code fullscreen | ltr rtl",
    ].join(" "),

    font_family_formats: [
      "Arial=Arial, Helvetica, sans-serif",
      "Helvetica=Helvetica, Arial, sans-serif",
      "Times New Roman='Times New Roman', Times, serif",
      "Georgia=Georgia, serif",
      "Tahoma=Tahoma, Geneva, sans-serif",
      "Verdana=Verdana, Geneva, sans-serif",
      "Courier New='Courier New', Courier, monospace",
    ].join(";"),

    font_size_formats: "8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 24pt 36pt",

    content_style: [
      "html,body{font-family: Arial, Helvetica, sans-serif; font-size:12pt;}",
      ".tiny-content{font-family: Arial, Helvetica, sans-serif; font-size:12pt;}",
      "img[data-mce-object]{display:inline-block;vertical-align:middle;max-width:100%;border:1px dashed #94a3b8;border-radius:12px;background-color:#f8fafc;background-repeat:no-repeat;background-position:center center;background-size:56px 56px;}",
      "img[data-mce-object='video'],img.mce-object-video{min-width:300px;min-height:150px;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='52' fill='%230f172ab8'/%3E%3Cpolygon points='50,38 88,60 50,82' fill='white'/%3E%3C/svg%3E\");}",
      "img[data-mce-object='audio'],img.mce-object-audio{min-width:300px;min-height:72px;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 120 120'%3E%3Ccircle cx='60' cy='60' r='52' fill='%230f172ab8'/%3E%3Cpath d='M50 46 L68 46 L82 34 L82 86 L68 74 L50 74 Z' fill='white'/%3E%3Cpath d='M88 46 Q98 60 88 74' fill='none' stroke='white' stroke-width='6' stroke-linecap='round'/%3E%3C/svg%3E\");}",
      "img[data-mce-selected='1']{border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,0.18);}",
    ].join(" "),

    base_url: BASE_URL_TINYMCE,
    suffix: SUFFIX,

    setup: function (editor) {
      editor.on("init", function () {
        var body = editor.getBody()
        if (body) {
          body.style.fontFamily = "Arial, Helvetica, sans-serif"
          body.style.fontSize = "12pt"
        }
      })
    },
  }

  ;(function ensureExternalPluginsMap(cfg) {
    var baseUrl = cfg.base_url || BASE_URL_TINYMCE
    var list = ("" + cfg.plugins).split(/\s+/).filter(Boolean)
    var map = Object.assign({}, cfg.external_plugins || {})
    list.forEach(function (name) {
      if (!map[name]) {
        map[name] = baseUrl + "/plugins/" + name + "/plugin" + (cfg.suffix || SUFFIX) + ".js"
      }
    })
    cfg.external_plugins = map
  })(BASE)

  window.CHAMILO_TINYMCE_BASE_CONFIG = BASE

  window.buildTinyMceConfig = function (local) {
    var base = window.CHAMILO_TINYMCE_BASE_CONFIG || {}
    var merged = Object.assign({}, base, local || {})

    var basePlugins = base.plugins || ""
    var localPlugins = (local && local.plugins) || ""
    merged.plugins =
      PLUGINS_POLICY === "base"
        ? basePlugins
        : Array.from(new Set((basePlugins + " " + localPlugins).trim().split(/\s+/))).join(" ")

    merged.external_plugins = Object.assign({}, base.external_plugins || {}, (local && local.external_plugins) || {})

    if (TOOLBAR_POLICY === "base" && base.toolbar) {
      merged.toolbar = base.toolbar
    } else if (base.toolbar && local && local.toolbar) {
      merged.toolbar = dedupeToolbar(base.toolbar, local.toolbar)
    }

    var csBase = base.content_style || ""
    var csLocal = (local && local.content_style) || ""
    merged.content_style = (csBase + " " + csLocal).trim()

    var baseSetup = base.setup
    var localSetup = local && local.setup
    merged.setup = function (ed) {
      if (typeof baseSetup === "function") baseSetup(ed)
      if (typeof localSetup === "function") localSetup(ed)
    }

    return merged
  }
})()
