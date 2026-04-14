const mammoth = require("mammoth");
const fs = require("fs");
const path = require("path");

const inputFile = process.argv[2];
const outputFile = process.argv[3] || null;

if (!inputFile) {
  console.error(JSON.stringify({ error: "Usage: node mammoth_convert.js <input.docx> [output.html]" }));
  process.exit(1);
}

if (!fs.existsSync(inputFile)) {
  console.error(JSON.stringify({ error: `File not found: ${inputFile}` }));
  process.exit(1);
}

const styleMap = [
  "p[style-name='Title'] => h1.doc-cover-title:fresh",
  "p[style-name='Subtitle'] => p.doc-cover-subtitle:fresh",
  "p[style-name='Heading 1'] => h2:fresh",
  "p[style-name='Heading 2'] => h3:fresh",
  "p[style-name='Heading 3'] => h4:fresh",
  "p[style-name='Heading 4'] => h5:fresh",
  "p[style-name='Caption'] => figcaption:fresh",
];

const options = {
  styleMap: styleMap,
  convertImage: mammoth.images.imgElement(function (image) {
    return image.read("base64").then(function (imageBuffer) {
      return {
        src: `data:${image.contentType};base64,${imageBuffer}`,
      };
    });
  }),
};

mammoth
  .convertToHtml({ path: inputFile }, options)
  .then(function (result) {
    const html = result.value;
    const messages = result.messages;

    const output = JSON.stringify({
      html: html,
      messages: messages,
    });

    if (outputFile) {
      fs.writeFileSync(outputFile, html, "utf8");
      console.log(JSON.stringify({ html: html, messages: messages, outputFile: outputFile }));
    } else {
      console.log(output);
    }
  })
  .catch(function (err) {
    console.error(JSON.stringify({ error: err.message }));
    process.exit(1);
  });
