# Cartella Font Self-Hosted

Questa cartella contiene i file `.woff2` utilizzati dal template Html56K.

## Come preparare i font

### Opzione 1: pyftsubset (consigliata)

```bash
pip install fonttools brotli

# Esempio con Inter Variable
pyftsubset Inter-VariableFont_opsz,wght.ttf \
  --output-file=inter-latin-var.woff2 \
  --flavor=woff2 \
  --layout-features='kern,liga,calt,locl' \
  --unicodes="U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+2074,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD"
```

### Opzione 2: Google Webfonts Helper

Visita https://gwfh.mranftl.com/fonts e scarica i subset Latin in formato WOFF2.

## File attesi

Secondo la configurazione in `css/fonts.css`:

- `inter-latin-var.woff2` — Font body (Regular, tutti i pesi)
- `inter-latin-var-italic.woff2` — Font body italic
- `heading-latin.woff2` — Font heading (se diverso dal body)

## Importante

I file `.woff2` non vengono generati automaticamente: devono essere preparati una volta e inseriti manualmente in questa cartella.
