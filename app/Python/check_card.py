# coding: utf-8

# from keras import models
from keras.models import model_from_json
from keras.preprocessing import image
import numpy as np
from PIL import Image
import io
import requests
# import matplotlib.pyplot as plt


def check_card(img_url):
    # only yamucha
    model = model_from_json(
        open('/home/vagrant/code/PIS/app/Python/yamucha_predict.json').read())
    # only yamucha
    model.load_weights(
        '/home/vagrant/code/PIS/app/Python/yamucha_predict.hdf5')
    CATEGORIES = ['HUM4-22', 'HUM4-22 PR']
    img = Image.open(io.BytesIO(requests.get(img_url).content))
    img = img.resize((250, 250))
    x = image.img_to_array(img)
    x = np.expand_dims(x, axis=0)
    features = model.predict(x)
    print(features[0])
    print(features[0].argmax())
    print(features[0].sum())
    print(CATEGORIES[features[0].argmax()])
    '''
    if features[0,0] == 1:
        print ('yamucha')
    elif features[0,1] == 1:
        print ('badakku')
    elif features[0,2] == 1:
        print ('songoku')
    elif features[0,3] == 1:
        print ('bejita')
    else:
        print('???')
    '''


check_card('https://img.fril.jp/img/342422377/m/965022399.jpg?1594466767')
