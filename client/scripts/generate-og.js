import { createCanvas } from 'canvas';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const WIDTH = 1200;
const HEIGHT = 630;

const canvas = createCanvas(WIDTH, HEIGHT);
const ctx = canvas.getContext('2d');

// --- Background ---
ctx.fillStyle = '#1a2e1d';
ctx.fillRect(0, 0, WIDTH, HEIGHT);

// --- Subtle grid texture ---
ctx.save();
ctx.strokeStyle = 'rgba(78, 114, 85, 0.12)';
ctx.lineWidth = 1;
const gridSize = 40;
for (let x = 0; x <= WIDTH; x += gridSize) {
  ctx.beginPath();
  ctx.moveTo(x, 0);
  ctx.lineTo(x, HEIGHT);
  ctx.stroke();
}
for (let y = 0; y <= HEIGHT; y += gridSize) {
  ctx.beginPath();
  ctx.moveTo(0, y);
  ctx.lineTo(WIDTH, y);
  ctx.stroke();
}
ctx.restore();

// --- Decorative circle accents ---
// Top-right large circle
ctx.save();
ctx.beginPath();
ctx.arc(WIDTH + 80, -80, 280, 0, Math.PI * 2);
ctx.strokeStyle = 'rgba(78, 114, 85, 0.35)';
ctx.lineWidth = 2.5;
ctx.stroke();
ctx.beginPath();
ctx.arc(WIDTH + 80, -80, 200, 0, Math.PI * 2);
ctx.strokeStyle = 'rgba(78, 114, 85, 0.20)';
ctx.lineWidth = 1.5;
ctx.stroke();
ctx.restore();

// Bottom-left large circle
ctx.save();
ctx.beginPath();
ctx.arc(-80, HEIGHT + 80, 280, 0, Math.PI * 2);
ctx.strokeStyle = 'rgba(78, 114, 85, 0.35)';
ctx.lineWidth = 2.5;
ctx.stroke();
ctx.beginPath();
ctx.arc(-80, HEIGHT + 80, 200, 0, Math.PI * 2);
ctx.strokeStyle = 'rgba(78, 114, 85, 0.20)';
ctx.lineWidth = 1.5;
ctx.stroke();
ctx.restore();

// --- Icon mark (rounded square with "S") ---
const iconX = 80;
const iconY = 200;
const iconSize = 100;
const radius = 22;

ctx.save();
// Draw rounded rectangle
ctx.beginPath();
ctx.moveTo(iconX + radius, iconY);
ctx.lineTo(iconX + iconSize - radius, iconY);
ctx.quadraticCurveTo(iconX + iconSize, iconY, iconX + iconSize, iconY + radius);
ctx.lineTo(iconX + iconSize, iconY + iconSize - radius);
ctx.quadraticCurveTo(iconX + iconSize, iconY + iconSize, iconX + iconSize - radius, iconY + iconSize);
ctx.lineTo(iconX + radius, iconY + iconSize);
ctx.quadraticCurveTo(iconX, iconY + iconSize, iconX, iconY + iconSize - radius);
ctx.lineTo(iconX, iconY + radius);
ctx.quadraticCurveTo(iconX, iconY, iconX + radius, iconY);
ctx.closePath();
ctx.fillStyle = '#4E7255';
ctx.fill();

// "S" letter
ctx.fillStyle = '#ffffff';
ctx.font = 'bold 68px sans-serif';
ctx.textAlign = 'center';
ctx.textBaseline = 'middle';
ctx.fillText('S', iconX + iconSize / 2, iconY + iconSize / 2 + 2);
ctx.restore();

// --- "SPOTIFLY" large bold text ---
ctx.save();
ctx.fillStyle = '#ffffff';
ctx.font = 'bold 88px sans-serif';
ctx.textAlign = 'left';
ctx.textBaseline = 'middle';
ctx.fillText('SPOTIFLY', iconX + iconSize + 32, iconY + iconSize / 2 + 2);
ctx.restore();

// --- Horizontal divider line ---
const dividerY = iconY + iconSize + 44;
ctx.save();
ctx.strokeStyle = '#4E7255';
ctx.lineWidth = 2;
ctx.beginPath();
ctx.moveTo(iconX, dividerY);
ctx.lineTo(WIDTH - 80, dividerY);
ctx.stroke();
ctx.restore();

// --- Tagline: "Olive Pest Monitoring System" ---
ctx.save();
ctx.fillStyle = '#a8c5aa';
ctx.font = 'bold 38px sans-serif';
ctx.textAlign = 'left';
ctx.textBaseline = 'top';
ctx.fillText('Olive Pest Monitoring System', iconX, dividerY + 32);
ctx.restore();

// --- Description text ---
ctx.save();
ctx.fillStyle = '#6e9b72';
ctx.font = '26px sans-serif';
ctx.textAlign = 'left';
ctx.textBaseline = 'top';
ctx.fillText('Monitor parcels  ·  Track actions  ·  Analyze weather & pests', iconX, dividerY + 96);
ctx.restore();

// --- Bottom-right pill/badge: "spotifly.app" ---
const badgeText = 'spotifly.app';
const badgePaddingX = 28;
const badgePaddingY = 14;
const badgeFontSize = 22;
ctx.save();
ctx.font = `${badgeFontSize}px sans-serif`;
const textMetrics = ctx.measureText(badgeText);
const badgeWidth = textMetrics.width + badgePaddingX * 2;
const badgeHeight = badgeFontSize + badgePaddingY * 2;
const badgeX = WIDTH - 80 - badgeWidth;
const badgeY = HEIGHT - 80 - badgeHeight;
const badgeRadius = badgeHeight / 2;

// Draw pill background
ctx.beginPath();
ctx.moveTo(badgeX + badgeRadius, badgeY);
ctx.lineTo(badgeX + badgeWidth - badgeRadius, badgeY);
ctx.quadraticCurveTo(badgeX + badgeWidth, badgeY, badgeX + badgeWidth, badgeY + badgeRadius);
ctx.lineTo(badgeX + badgeWidth, badgeY + badgeHeight - badgeRadius);
ctx.quadraticCurveTo(badgeX + badgeWidth, badgeY + badgeHeight, badgeX + badgeWidth - badgeRadius, badgeY + badgeHeight);
ctx.lineTo(badgeX + badgeRadius, badgeY + badgeHeight);
ctx.quadraticCurveTo(badgeX, badgeY + badgeHeight, badgeX, badgeY + badgeHeight - badgeRadius);
ctx.lineTo(badgeX, badgeY + badgeRadius);
ctx.quadraticCurveTo(badgeX, badgeY, badgeX + badgeRadius, badgeY);
ctx.closePath();
ctx.fillStyle = 'rgba(78, 114, 85, 0.30)';
ctx.fill();
ctx.strokeStyle = '#4E7255';
ctx.lineWidth = 1.5;
ctx.stroke();

// Badge text
ctx.fillStyle = '#a8c5aa';
ctx.font = `${badgeFontSize}px sans-serif`;
ctx.textAlign = 'center';
ctx.textBaseline = 'middle';
ctx.fillText(badgeText, badgeX + badgeWidth / 2, badgeY + badgeHeight / 2);
ctx.restore();

// --- Save the image ---
const outputPath = path.join(__dirname, '..', 'public', 'og-image.png');
const buffer = canvas.toBuffer('image/png');
fs.writeFileSync(outputPath, buffer);
console.log(`OG image saved to: ${outputPath}`);
console.log(`Dimensions: ${WIDTH}x${HEIGHT}`);
