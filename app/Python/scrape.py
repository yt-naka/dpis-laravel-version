import requests
from bs4 import BeautifulSoup
import sys
import re
import json
import time
import vardata
import io
# import subprocess
# import urllib.parse

# from keras import models
from keras.models import model_from_json
from keras.preprocessing import image
import numpy as np
from PIL import Image
# import matplotlib.pyplot as plt
# sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')

args = sys.argv
if args[2] == 'yafuoku' or args[2] == 'rakuma':
    serch_words = args[1].split('+')
    card_name = serch_words[0]
    if len(serch_words) == 3:  # ex ヤムチャ+HUM4-22+ドラゴンボールヒーローズ
        card_number = serch_words[1]  # ex HUM4-22
    elif len(serch_words) == 4:  # ex ヤムチャ+HUM4-22+PR+ドラゴンボールヒーローズ
        card_number = f"{serch_words[1]}+{serch_words[2]}"  # ex HUM4-22+PR
    FLEMAS_NAME = ["yafuoku", "rakuma"]
    MAX_SAMPLE_NUM = 10
    RAKUMA_PRODUCTS_MAX_NUM_IN_ONE_PAGE = 36

# PRは勝手に付けた非公式なカード番号のため検索する時は+PRを削除する
data = {
    "yafuoku": {
        "url": "https://auctions.yahoo.co.jp/closedsearch/closedsearch?"
        f"p={args[1].replace('+PR', '')}&va={args[1].replace('+PR', '')}"
        "&b=1&n=50&select=6",
        "products": {
            "list": [],
            "sum_prices": {
                "sample_num_1": 0,
                "sample_num_5": 0,
                "sample_num_10": 0
            },
            "average_prices": {
                "sample_num_1": 0,
                "sample_num_5": 0,
                "sample_num_10": 0
            },
            "success_num": 0,
        },
        "selector": {
            "all_products": "li.Product",
            "title": "h3.Product__title",
            "price": "span.Product__priceValue",
            "image": "img",
            "url": "div.Product__image > a",
            "end_date_time": "div:nth-child(2) > span.Product__time",
            "last_button": "li.Pager__list.Pager__list--next > a"
        },
    },
    "rakuma": {
        "url": "https://fril.jp/s"
        "?order=desc&page=1"
        f"&query={args[1].replace('+PR', '')}&sort=created_at"
        "&transaction=soldout",
        "products": {
            "list": [],
            "sum_prices": {
                "sample_num_1": 0,
                "sample_num_5": 0,
                "sample_num_10": 0
            },
            "average_prices": {
                "sample_num_1": 0,
                "sample_num_5": 0,
                "sample_num_10": 0
            },
            "success_num": 0
        },
        "selector": {
            "all_products": "div.item",
            "title": "p.item-box__item-name",
            "price": "p.item-box__item-price",
            "image": "img",
            "url": "a",
            "last_button": "div.search_tab > div.hidden-xs > nav>span.last"
        },
    }
}

# card_number : ex HUM4-22+PR


def check_card(card_number, img_url):
    # only yamucha
    model = model_from_json(
        open('/home/vagrant/code/PIS/app/Python/all_card_predict.json').read())
    # only yamucha
    model.load_weights(
        '/home/vagrant/code/PIS/app/Python/all_card_predict.hdf5')
    CATEGORIES = vardata.all_card_number
    img = Image.open(io.BytesIO(requests.get(img_url).content))
    img = img.resize((250, 250))
    x = image.img_to_array(img)
    x = np.expand_dims(x, axis=0)
    try:
        features = model.predict(x)
        if card_number.replace('+', ' ') == CATEGORIES[features[0].argmax()] \
            or card_number.replace('+CP', '')\
                == CATEGORIES[features[0].argmax()]:
            return True
        return False
    except ValueError:  # 画像サイズが250×250未満の時
        return False


def get_title_error_words_num(product_title):
    product_title = product_title[0].get_text()
    # print(product_title.encode('utf-8'))
    error_words = re.findall(vardata.unavailable_words, product_title)
    return error_words


def set_average_price_each_sample_num(data, flema_name):

    # sample number 1
    try:
        data[flema_name]["products"]["average_prices"]["sample_num_1"]\
            = data[flema_name]["products"]["list"][0]["price"]
    except IndexError:
        data[flema_name]["products"]["average_prices"]["sample_num_1"]\
            = 0

    # sample number 5
    sample_num_5_price_sum = 0
    i = 0
    for product in data[flema_name]["products"]["list"]:
        if product["status"] == 1:
            i += 1
            sample_num_5_price_sum += product["price"]
            if i >= 5:
                break
    try:
        data[flema_name]["products"]["average_prices"]["sample_num_5"]\
            = round(sample_num_5_price_sum / i)
    except ZeroDivisionError:
        data[flema_name]["products"]["average_prices"]["sample_num_5"]\
            = 0

    # sample number 10
    sample_num_10_price_sum = 0
    j = 0
    for product in data[flema_name]["products"]["list"]:
        if product["status"] == 1:
            j += 1
            sample_num_10_price_sum += product["price"]
            if j >= 10:
                break
    try:
        data[flema_name]["products"]["average_prices"]["sample_num_10"]\
            = round(sample_num_10_price_sum / j)
    except ZeroDivisionError:
        data[flema_name]["products"]["average_prices"]["sample_num_10"]\
            = 0


def set_product(data, product_origin_data, flema_name):
    image_index = 0
    if flema_name == 'rakuma':  # special case
        image_index = 1
    product_infos = {}
    product_infos["title"] = product_origin_data.select(
        data[flema_name]["selector"]["title"])
    product_infos["price"] = product_origin_data.select(
        data[flema_name]["selector"]["price"])
    product_infos["image"] = product_origin_data.select(
        data[flema_name]["selector"]["image"])
    product_infos["url"] = product_origin_data.select(
        data[flema_name]["selector"]["url"])
    if flema_name == 'yafuoku':
        product_infos["end_date_time"] = product_origin_data.select(
            data[flema_name]["selector"]["end_date_time"])
        aaa = '2020/' + \
            product_infos["end_date_time"][0].get_text(strip=True) + ':00'
        aaa = aaa.replace('/', '-')
    elif flema_name == 'rakuma':
        aaa = 0
    title_error_words_num = get_title_error_words_num(
        product_infos["title"])

    if title_error_words_num == []:  # check_cardは別でやる。処理が遅いため
        status = 1
        data[flema_name]["products"]["success_num"] += 1
    else:
        status = 0

    price_origin = product_infos["price"][0].get_text(strip=True)
    price_str = re.findall('[0-9]+', price_origin.replace(',', ''))

    data[flema_name]["products"]["list"].append({
        "title": product_infos["title"][0].get_text(strip=True),
        "price": int(price_str[0]),
        "image": product_infos["image"][image_index].get("src"),
        "url": product_infos["url"][0].get("href"),
        "end_date_time": aaa,
        "status": status
    })


def scrape_and_set_data(flema_name):
    time.sleep(3)  # important

    data[flema_name]["origin_data"] = requests.get(
        data[flema_name]["url"].encode('utf-8', 'surrogateescape'))
    data[flema_name]["soup"] = BeautifulSoup(
        data[flema_name]["origin_data"].content, "html.parser")
    data[flema_name]["all_products"] = data[flema_name]["soup"].select(
        data[flema_name]["selector"]["all_products"])

    for i, product in enumerate(data[flema_name]["all_products"]):
        set_product(data, product, flema_name)

        if data[flema_name]["products"]["success_num"]\
                >= MAX_SAMPLE_NUM:
            break

        if flema_name == 'yafuoku' and len(
                data[flema_name]["products"]["list"])\
                >= RAKUMA_PRODUCTS_MAX_NUM_IN_ONE_PAGE:
            break

    set_average_price_each_sample_num(data, flema_name)


def show_data():
    print('yafuoku', len(data["yafuoku"]["products"]["success"]))
    print('yafuoku', len(data["yafuoku"]["products"]["error"]))
    print(len(data["rakuma"]["products"]["success"]))
    print(len(data["rakuma"]["products"]["error"]))
    for i, flema_name in enumerate(data):
        print(flema_name)
        for product_infos in data[flema_name]["products"]["success"]["list"]:
            print(product_infos["image"])
            # check_card(product_infos["image"])
            print(product_infos["url"])
            print(product_infos["title"].encode())
            print(product_infos["price"])


def show_products_object():
    output = {}
    output["yafuoku"] = data["yafuoku"]["products"]
    output["rakuma"] = data["rakuma"]["products"]
    print(json.dumps(output))


def show_any_flema_products_object(flema_name):
    data[flema_name]["products"]["list"].reverse()
    print(json.dumps(data[flema_name]["products"]))


def output():
    print(data["yafuoku"]["products"]["success"]
          ["average_prices"]["sample_num_1"])
    print(data["yafuoku"]["products"]["success"]
          ["average_prices"]["sample_num_5"])
    print(data["yafuoku"]["products"]["success"]
          ["average_prices"]["sample_num_10"])


def main():
    for flema_name in FLEMAS_NAME:
        scrape_and_set_data(flema_name)


if args[2] == 'yafuoku':
    scrape_and_set_data('yafuoku')
    show_any_flema_products_object('yafuoku')
elif args[2] == 'rakuma':
    scrape_and_set_data('rakuma')
    show_any_flema_products_object('rakuma')
elif args[2] == 'UpdatePrice':
    main()
    show_products_object()
    # output()
elif args[2] == 'ProductController':
    main()
    show_products_object()
    # show_data()
elif args[3] == 'CheckCardImg':
    if check_card(args[1], args[2]):
        print(1)
    else:
        print(0)

'''
for aaa in data["rakuma"]["products"]["list"]:
    print(aaa["end_date_time"])
'''
