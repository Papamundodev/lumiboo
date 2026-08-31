import path from 'path';
import { defineConfig } from 'vite';

const ROOT = path.resolve(__dirname, '../../..');
const BASE = __dirname.replace(ROOT, '').replace(/\\/g, '/');

export default defineConfig({
  base: process.env.NODE_ENV === 'production' ? `${BASE}/dist/` : '/',
  server: {
    cors: true,
    origin: 'http://localhost:5173',
  },
  build: {
    manifest: true,
    assetsDir: '.',
    outDir: 'dist',
    emptyOutDir: true,
    sourcemap: true,
    rollupOptions: {
      input: [
        'resources/scripts/main.js',
        'resources/scripts/popover.js',
        'resources/scripts/slider.js',
        'resources/styles/main.scss',
      ],
      output: {
        entryFileNames: '[hash].js',
        assetFileNames: '[hash].[ext]',
      },
    },
  },
  plugins: [
    {
      name: 'php-reload',
      configureServer(server) {
        // Explicitly watch PHP files (they're not in the JS/CSS dependency graph)
        server.watcher.add('**/*.php');
      },
      handleHotUpdate({ file, server }) {
        if (file.endsWith('.php')) {
          server.ws.send({ type: 'full-reload' });
        }
      },
    },
  ],
});