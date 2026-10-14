"""Build Nazra import files from supplied Word files and transcribed drink photos."""
from pathlib import Path
import json, zipfile, xml.etree.ElementTree as ET, shutil
ROOT = Path(__file__).resolve().parents[1]
SOURCE = Path('C:/Users/mudim/OneDrive/Desktop/nazra')
OUT = ROOT / 'output' / 'nazra'
OUT.mkdir(parents=True, exist_ok=True)
categories = []
def add(title, rows):
    items = []
    for row in rows.split(';'):
        if not row.strip(): continue
        parts = row.strip().split('|')
        items.append({'name': parts[0].strip(), 'price': int(parts[1]), 'description': parts[2].strip() if len(parts)>2 else '', 'is_available': True})
    categories.append({'name': title, 'items': items})
add('All-day · Breakfast', "French Toast|400;Continental Breakfast|600;English Breakfast|600;Mini Breakfast|550;Spinach Omelet|400;Normal Omelet|400;Cheese Omelet|450;Special Omelet|500;Scrambled Egg|450;Egg Sandwich|450;Fetira with Egg|600;Fetira with Honey|600;Foul Madams|550;Normal Foul|500;Special Foul|550;Breakfast Buffet|600;Chechebsa with Egg|450;Chechebsa with Butter|450;Chechebsa with Honey|450;Fasting Firfir|500;Quanta Firfir|600;Siga Firfir|600;Tuna Firfir|700")
add('All-day · Cold starters', 'Garden Salad|500|Salad greens, tomato, onion, carrot, olive, pepper and vinaigrette dressing.;Salad Nicosia|750|Salad greens, green beans, olive, onion, hot peppers, tomato, topped with tuna and boiled eggs.;Nazra Hotel Special Salad|700;Assorted Salad|700;Tuna Salad|800')
add('All-day · Hot starters', 'Vegetable Soup|500;Tomato Cream Soup|500;Chicken Cream Soup|550;Beef Soup|550;Fish Soup|500')
add('All-day · Vegetarian', 'Vegetarian Potpourri|500|Freshly oven-baked Mediterranean vegetables.;Vegetarian Fruity Curry|550|Zucchini, paprika, champignons, banana and pineapple served with fresh curry sauce.;Roasted Vegetable|500')
add('All-day · Pasta', 'Alla Napolitano|550|Light tomato sauce.;Alla Arrabbiata|550|Light tomato sauce, chilies and fresh garlic.;Pasta Primavera|550|Light tomato sauce with vegetables.;Alla Bolognese|650|Beef, tomato and herbs.;Alla Carbonara|700|Bacon, cream, cream cheese and egg yolk.;Champignon Alla Penne|650|Mushroom and cream sauce.;Alla Tuna|750;Fettuccine Alfredo|700')
add('All-day · Rice', 'Rice with Vegetable|500|Assorted vegetables served with rice.;Rice with Beef Sausage|600;Rice with Curried Chicken|700;Rice with Tomato Sauce|500')
add('All-day · Beef', 'Fillet Steak|900|With mushroom and cream.;Steak with Pepper|900;Beef Stroganoff|850|With mushroom and cream.;Grilled Steak|900|Beef tenderloin with pepper and cream.;Grilled Sausage|850|Five pieces of sausage and sauce.')
add('All-day · Chicken', 'Health Chicken Breasts|850|Grilled chicken breast with rice and vegetables.;Chicken Schnitzel|850|Lightly crumbed chicken fillet.;Chicken Stroganoff|850|With mushroom and cream.;Chicken Nuggets|850')
add('All-day · Roasted', 'Roasted Beef|900;Roasted Chicken|900')
add('All-day · Fish', 'Grilled Tilapia|650;Goulash|650;Breaded Baked Tilapia|700;Cutlet|700')
add('All-day · Sandwiches', 'Vegetable Sandwich|500;Club Sandwich|650|Toasted triple-decker sandwich with shredded chicken.;Tuna Sandwich|750|Tuna, chopped onions and toasted bread, served with fries.;Chicken Sandwich|700|Served with French fries.;Steak Sandwich|750|Served with French fries.;French Fries|450')
add('All-day · Ethiopian corner', 'Fasting Fir-Fir|500;Quanta Fir-Fir|600;Tuna Fir-Fir|700;Shero/Quanta|600;Bozena Shiro|600;Shero/Tomato|550;Chekina Tibs|850;Grilled Tibes|850;Shero|500')
add('All-day · Combination', 'Non-fasting Combo|850|Tibs firfir, shero, vegetables, rice and beef.;Fasting Combo|650|Shero, vegetables, spaghetti, rice and tomato.')
add('Room service · Breakfast', 'French Toast|450;Normal Omelet|450;Special Omelet|570;Scrambled Egg|500;Egg Sandwich|500;Normal Foul|550;Special Foul|600;Chechebsa with Egg|500;Chechebsa with Butter|500;Fasting Firfir|550;Siga Firfir|600')
add('Room service · Cold starters', 'Garden Salad|550|Salad greens, tomato, onion, carrot, olive, pepper and vinaigrette dressing.;Nazra Hotel Special Salad|750;Tuna Salad|800')
add('Room service · Hot starters', 'Hot Soup|550;Vegetable Soup|550;Chicken Cream Soup|600;Beef Soup|600')
add('Room service · Vegetarian', 'Vegetarian Potpourri|550|Freshly oven-baked Mediterranean vegetables.;Roasted Vegetable|550')
add('Room service · Pasta', 'Alla Napolitano|600|Light tomato sauce.;Alla Bolognese|600|Beef, tomato and herbs.;Alla Carbonara|750|Bacon, cream, cream cheese and egg yolk.;Alla Tuna|800')
add('Room service · Rice', 'Rice with Vegetable|550|Assorted vegetables served with rice.;Rice with Beef Sausage|650;Rice with Curried Chicken|750;Rice with Tomato Sauce|550')
add('Room service · Beef', 'Fillet Steak|900|With mushroom and cream.;Beef Stroganoff|850|With mushroom and cream.;Grilled Steak|900|Beef tenderloin with pepper and cream.;Roasted Beef|900')
add('Room service · Chicken', 'Health Chicken Breasts|850|Grilled chicken breast with rice and vegetables.;Chicken Stroganoff|850|With mushroom and cream.;Roasted Chicken|900')
add('Room service · Fish', 'Goulash|650;Cutlet|700')
add('Room service · National dishes', 'Tuna Fir-Fir|700;Bozena Shiro|650;Shero/Tomato|600;Chekina Tibs|850;Grilled Tibes|850')
add('Drinks · Local wines', ';'.join(n+'|2800' for n in ['Acacia Dry Red','Acacia Medium Sweet Red','Acacia Rose','Acacia Medium Sweet White','Rift Valley Merlot','Rift Valley Cabernet Sauvignon','Rift Valley Syrah','Rift Valley Dry Red','Rift Valley Medium Dry Red']))
add('Drinks · Imported wines', 'Glass of Wine|600;Alexis Lichine Cabernet Sauvignon|3500;Famille Castel Medium Sweet (France)|3500;Famille Castel Medium Sweet Chardonnay Muscat (France)|4000;Famille Castel Grand Reserve Chardonnay Muscat (France)|4000;Nederburg Edelroud (South Africa)|3500;Two Ocean Cabernet Merlot (South Africa)|3500;Reserve De France Cabernet Sauvignon|3500;Reserve De France Merlot|3500;Famille Castel Cuvée De France Dry Red|3500;Famille Castel Cuvée De France Medium Sweet|3500;Famille Castel Côte Du Rhone (France)|4000;Famille Castel Grande Réserve Syrah (France)|3500;Drostedy Hof Claret Select 75cl Red|3500;Bardolino Classic Zonin 75cl|3500;Pavese Pinot Nero 75cl|3500;Valpolocella Classico Doc Red 75cl|3500')
def drinks(title, rows):
    variants=[]
    for row in rows.split(';'):
        name,shot,bottle=row.split('|')
        if shot: variants.append(name+' · Shot|'+shot)
        if bottle: variants.append(name+' · Bottle|'+bottle)
    add('Drinks · '+title, ';'.join(variants))
drinks('Spirits', "Red Label 1l|500|11000;Red Label 50cl||6000;Red Label 75cl||8000;White Horse|450|9500;Jack Daniel's|500|13000;Double Black Label|600|19000;Black Label 1l|550|16500;Black Label 75cl||12000;Black Label 50cl||8500;Chivas Regal 12 1l|500|14000;Gold Label|650|19000;William Grant's 100cl|500|14000;Dimple 75cl|500|14000;Glen Fiddich Special Reserve 100cl|500|14000;Blue Label 1l||45000;Jameson|450|11000")
drinks('Gin', "Gordon's Dry Gin|450|10000")
drinks('Aperitif', 'Campari 75cl|450|9000;Pastis 51 1l|450|9000;Fernet Branca 70cl|500|9500;Martini Rosso 1l|500|10000;Martini Bianco 1l|500|10000')
drinks('Liquor', 'Malibu 1l|450|8000;Baileys 1l|450|8000;Amarula Cream 1l|450|8000;Cointreau|470|9000;Tia Maria 1l|470|9000;Sambuca 70cl|450|8500;Amaretto 70cl|450|8000;Smirnoff 70cl|450|8000')
drinks('Tequila', 'Camino Tequila|400|8000')
drinks('Vodka', 'Stolichnaya 75cl|450|9000;Absolut Vodka 1l|480|10000;Grey Goose|450|9000;Ciroc Vodka 1l|500|11000;Skyy Vodka 50cl||6000;Skyy Vodka 100cl|450|10000;Winter Palace 1l|450|10000;Winter Palace ½l||6000')
drinks('Rum', 'Bacardi Blanco|450|9000;Bacardi Gold|450|9000')
drinks('Cognac', 'Rémy Martin V.S.O.P 70cl|650|20000;Rémy Martin V.S. 70cl|550|17000;Courvoisier V.S.O.P 1l|850|30000;Courvoisier V.S. 1l|550|17000')
add('Drinks · Beer', 'St. George|180;Habesha|180;Heineken|300')
add('Drinks · Non-alcoholic', 'Seasonal Juice|250;Soft Drinks|120;Sparkling Water (Ambo)|120;Spring Water ½l|100;Spring Water 1l|120;Spring Water 2l|150')
add('Drinks · Cocktails', 'Long Island Iced Tea|800|Vodka, tequila, rum, gin, coca and lemon juice.;BE52|800|Tia Maria, Baileys and Vointreau (as supplied).;Margarita Cocktail|850|Cointreau, Stolichnaya, Bacardi, Red Label and iced lemon juice.;Screw Driver|800|Stolichyana vodka and orange juice.;Kamakazi|800|Stolichyana, Cointreau and lemon juice.')
add('Drinks · Hot drinks', 'Tea|80;Special Tea Normal|200;Special Tea Alcohol|200;Coffee|120;Macchiato|140;Fasting Macchiato|130;Café Latte|120;Tea with Coffee|100;Cappuccino|130;Flavored Tea|80;Ouzo with Tea|140;Hot Chocolate|140;Tea with Honey|150;Milk with Honey|150')
manifest={'format':'zemtab-menu','version':1,'photo_mode':'text_only','price_note':'All prices are in Ethiopian birr and include 10% service charge and 15% VAT. Room service: dial 147.','categories':categories}
menu_json=json.dumps(manifest,ensure_ascii=False,indent=2)
(OUT/'nazra-menu.json').write_text(menu_json,encoding='utf-8')
with zipfile.ZipFile(OUT/'nazra-menu.zip','w',zipfile.ZIP_DEFLATED) as z:z.writestr('menu.json',menu_json)
ns={'w':'http://schemas.openxmlformats.org/wordprocessingml/2006/main'}
with zipfile.ZipFile(SOURCE/'NAZRA directory FOR QR CODE.docx') as z:
    root=ET.fromstring(z.read('word/document.xml'))
paragraphs=[''.join(t.text or '' for t in p.findall('.//w:t',ns)).strip() for p in root.findall('.//w:body/w:p',ns)]
headings=['Airport','Baby Cot','Bar and Restaurant','Beds (Extra)','Bell Attendants','Breakfast','Check Out Time','Credit Cards','Credit Limit','Currency Exchange','Electrical Supply & Voltage','Emergency Numbers','Fire Precautions /Emergency Evacuation','Internet','Keys','Lost & Found','Luggage Service & Storing','Maintenance','Meeting Space','Messages','Mini Bar','Parking','Room Account','Room Service','Safety Deposit Box','Security','Shops','Taxis','Television','Visitors/Extra person','Wake Up Calls']
sections=[];title='Welcome';body=[]
for line in paragraphs:
    if not line:continue
    if line in headings:
        if body:sections.append({'title':title,'body':'\n\n'.join(body)})
        title=line;body=[]
    else:body.append(line)
if body:sections.append({'title':title,'body':'\n\n'.join(body)})
with zipfile.ZipFile(SOURCE/'All Day A5 Menu for qr code.docx') as z:
    menu_root=ET.fromstring(z.read('word/document.xml'))
menu_paragraphs=[''.join(t.text or '' for t in p.findall('.//w:t',ns)).strip() for p in menu_root.findall('.//w:body/w:p',ns)]
start=menu_paragraphs.index('WELCOME TO NAZRA HOTEL')+1
end=menu_paragraphs.index('BREAKFAST')
sections.insert(1, {'title':'About Nazra Hotel','body':'\n\n'.join(t for t in menu_paragraphs[start:end] if t)})
board={'format':'zemtab-information','version':1,'title':'Hotel Guide','sections':sections}
(OUT/'nazra-information.json').write_text(json.dumps(board,ensure_ascii=False,indent=2),encoding='utf-8')
shutil.copyfile(SOURCE/'nazra logo.png',OUT/'nazra-logo.png')
(OUT/'README.md').write_text("""# Nazra import files

1. Deploy the updated ZemTab code first.
2. Admin > Database > Replace menu from import package: select Nazra and upload nazra-menu.zip. This REPLACES the selected venue's existing menu.
3. In the information-board importer directly below, select Nazra and upload nazra-information.json. This replaces only its information board.
4. Upload nazra-logo.png in Nazra's Settings if needed. Select Menu only and Text only in the admin account settings.
5. Open both guest QR destinations before printing the saved cards.

Food prices are from the respective all-day and room-service Word documents. Drink prices are from photos 2-9. Shot and bottle entries are separate items. Taxes are already included in the provided prices.

Source ambiguities retained for hotel review:
- All-day fish item is written as 'Breaded Baked Tilapia' (700), followed by 'Cutlet' (700); no dish name was invented.
- Cocktail name BE52 and ingredient Vointreau are supplied this way; confirm the intended names/ingredients.
- Directory uses reception 0 in most places and 9 for electrical adapters; retained as supplied.
- Directory mentions an additional 200 birr breakfast room-service charge; retained there, not automatically added to food prices.
- Directory retains the supplied key replacement amounts (1500 birr / 15 USD) and adapter fee (3 USD); hotel should confirm them.

These files do not alter subscriptions, account service mode, login credentials or QR designs.
""",encoding='utf-8')
print(f'Created {len(categories)} categories, {sum(len(c["items"]) for c in categories)} items, {len(sections)} guide sections in {OUT}')
