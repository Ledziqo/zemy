# Actual device photography

The landing showcase uses real Apple product images, with HTML/CSS screen overlays. The device frames are not generated or drawn.

- Silver iMac: https://www.apple.com/imac/ — image https://www.apple.com/v/imac/y/images/overview/closer-look/colors_pf_silver__di535sz3p16q_large_2x.jpg
- Burgundy iPhone 18 Pro rear: https://www.apple.com/shop/buy-iphone/iphone-18-pro — CDN asset iphone-18-pro-finish-select-burgundy-202609 (PNG alpha).
- iPhone 18 Pro front: same product page — CDN asset iphone-compare-iphone-18-pro-202609 (PNG alpha).

Files are local under public/uploads/product/apple-*. Screen images are workboard-retina.png and menu-retina.png, captured at 3x from disposable preview data. The incoming sample order is Tiramisu, 750 ETB. Camera hardware is preserved from the real phone photograph.

Verified image decoding and page overflow at 1440, 768, 375 and 320 pixel viewport widths. The local preview remains on port 8766. This change is not pushed or deployed.
