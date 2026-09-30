#!/usr/bin/env python3
"""Build the Elementor data for the 2026 R.A.M. Management design.

Writes build/elementor/<doc>-<lang>.json: the Home, About and Contact pages plus the header and footer
templates, in French (primary) and English. Every section is an Elementor container and every piece of
text, image, icon or link is a native widget, so the pages stay editable in Elementor; the look comes
from the "rv-" classes in theme/ram-hello-child/assets/ram-v2.css.

Icons are written as "__ICON_<name>__" placeholders that build/apply.php swaps for the Media Library
attachments it creates from build/icons/.
"""
import hashlib
import json
import os

HERE = os.path.dirname(os.path.abspath(__file__))
UPLOADS = 'https://ram-attorney.wsdfy.com/wp-content/uploads/2026/09/'

# Media Library attachments (uploaded from design/images and the original logo files).
IMG = {
    'logo': (36, 'ram-logo-transparent.png'),
    'logo_white': (37, 'ram-logo-white.png'),
    'hero': (146, 'ram-v2-hero-montreal.jpg'),
    'about_banner': (147, 'ram-v2-about-banner.jpg'),
    'justice': (148, 'ram-v2-lady-justice.jpg'),
    'quote': (149, 'ram-v2-quote-taxes.jpg'),
    'contact_banner': (150, 'ram-v2-contact-banner.jpg'),
}

# Copy from the design bundle (RAM-Website-HTML), French and English.
T = {
    'en': {
        'nav': [('Home', '/en/'), ('About', '/en/about/'), ('Contact', '/en/contact/')],
        'logo_alt': 'R.A.M. Management – Attorneys at Law',
        'home_label': 'THE COMPANY',
        'home1': 'RAM Management - Attorneys at Law provides legal and business consulting services.',
        'home2': 'In addition to offering corporate and commercial law services to small and medium-sized businesses, we are specialized in assisting non-resident entertainers and entertainment companies successfully navigate their Canadian tax matters.',
        'about_title': 'ABOUT',
        'about1': 'Founded in 1994 as a company primarily managing artists in the music industry and providing legal services to companies and organizations in the music sector, RAM Management has grown to provide legal counsel to all areas of business.',
        'about_rest': [
            'RAM Management works closely with small and medium-size businesses, assisting them with their contractual and commercial relationships and corporate structure. It also works closely with business owners in developing strategies to achieve growth and enhance the value of their business.',
            'Often being called upon to hire American entertainers on behalf of our Canadian clients for their private and corporate events, we began assisting some of these entertainers in processing their Canadian tax waivers.',
            'Appreciating our work, non-resident entertainers began hiring our office directly to process tax waivers for all of their performances in Canada; many of these relationships still exist today.',
        ],
        'about5': 'Today, our expertise has expended to all aspects of tax compliance for non-resident entertainers as well as the contractors and vendors that work on their performances in Canada. We can assist with such matters as:',
        'services': [
            'Waivers of Federal Regulation 105 and Quebec Regulation 1016 withholding',
            'R105 and Part XIII Tax withholdings on employees, contractors, and vendors',
            'Income tax return and information return filings',
            'Income tax and withholding audits',
            'Objections and other representations before the CRA and Tax Court of Canada',
        ],
        'about6': 'Over the past decade, we have witnessed the greater attention that the CRA has paid to non-resident entertainers and their operations and believe it is more important than ever for non-residents working in Canada to ensure they are addressing all their Canadian tax obligations.',
        'team_label': 'OUR TEAM',
        'team': [
            (['RAM Management is owned by Richard Dermer, a long-time resident of Montreal and small business owner himself from 1985 to 1994.',
              'Richard graduated Vassar College with the Bachelor of Arts in History, followed by graduating with a Bachelor of Civil Law from McGill Law School and becoming a member of the Quebec Bar in 1994.',
              'Richard has served on the board and committees of various community-based organizations and served on the Board and Executive of the Canadian Independent Record Production Association from 2002-2008.'], 'richard'),
            (['Administration and Director of International Client Services Nadine Benny has been with RAM Management for over 20 years. Graduating from Concordia University in 2005 with a Bachelor of Commerce in Marketing, Nadine participated in the Institute for Co-operative Education program where she completed internships in advertising, artist management, and the non-profit sector. A classically trained pianist, Nadine completed the McGill Conservatory of Music program in 1999. Nadine speaks French and English fluently and completed a Certificate in Law at Université de Montréal in 2011.'], 'nadine'),
            (['International Client Coordinator Kayla Papps joined RAM Management shortly after graduating from McGill University in 2019 with a Bachelor of Arts in Psychology, Kayla has become an integral part of the team, maintaining client relationships and managing the intricacies of the ever-evolving CRA compliance requirements.'], 'kayla'),
        ],
        'contact_title': 'CONTACT',
        'address': ['R.A.M. Management', '5165 Queen Mary Road, suite 405', 'Montreal, Quebec', 'Canada', 'H3W 1X7'],
        'tel': 'Tel: 514.369.4412',
        'fax': 'Fax: 514.489.5155',
        'form_label': 'Have any questions?',
        'form_title': 'Get in touch with us',
        'map_title': 'MAP',
        'footer_contact': 'Contact',
        'footer_pages': 'Pages',
    },
    'fr': {
        'nav': [('Accueil', '/'), ('À propos', '/a-propos/'), ('Contact', '/nous-joindre/')],
        'logo_alt': 'R.A.M. Management – Avocats',
        'home_label': "L'ENTREPRISE",
        'home1': 'RAM Management - Avocats offre des services de consultation juridique et commerciale.',
        'home2': "En plus d'offrir des services de droit corporatif et commercial aux petites et moyennes entreprises, nous sommes spécialisés dans l'aide aux artistes non-résidents et aux entreprises de divertissement en lien avec leurs questions fiscales canadiennes.",
        'about_title': 'À PROPOS',
        'about1': "Fondée en 1994 en tant qu'entreprise gérant principalement des artistes de l'industrie musicale et offrant des services juridiques à des entreprises et organisations du secteur, RAM Management a évolué et offre à présent des conseils juridiques à tous les domaines d'affaires.",
        'about_rest': [
            "RAM Management travaille en étroite collaboration avec les petites et moyennes entreprises, les aidant dans leurs relations contractuelles et commerciales ainsi que dans leur structure d'entreprise. Elle collabore également avec les propriétaires d'entreprise pour élaborer des stratégies visant à accroître la croissance et la valeur de leur entreprise.",
            'Souvent sollicités pour engager des artistes américains au nom de nos clients canadiens pour leurs événements privés et corporatifs, nous avons commencé à aider certains de ces artistes à traiter leurs demandes d’exemptions fiscales canadiennes.',
            "Appréciant notre travail, les artistes non-résidents ont commencé à engager directement RAM Management pour traiter les exemptions d'impôts pour toutes leurs performances au Canada; beaucoup de ces relations existent encore aujourd'hui.",
        ],
        'about5': 'Notre expertise s’étend aujourd’hui à tous les aspects de la conformité fiscale pour les artistes non-résidents ainsi que pour les entrepreneurs et fournisseurs qui travaillent sur leurs spectacles au Canada. Nous pouvons vous aider sur des questions telles que :',
        'services': [
            'Dispenses des retenues à la source du Règlement 105 fédéral et Règlement 1016 du Québec',
            'Retenues R105 et Partie XIII pour les employés, entrepreneurs et fournisseurs',
            "Déclaration de revenus et déclaration d'information",
            "Vérifications d'impôt sur le revenu et des retenues à la source",
            "Objections et autres représentations auprès de l'ARC et la Cour fiscale du Canada",
        ],
        'about6': "Au cours de la dernière décennie, nous avons été témoins de l'attention accrue portée par l'ARC aux artistes non-résidents et à leurs opérations, et nous croyons qu'il est plus important que jamais pour les non-résidents travaillant au Canada de s'assurer qu'ils respectent toutes leurs obligations fiscales canadiennes.",
        'team_label': 'NOTRE ÉQUIPE',
        'team': [
            (["RAM Management fut fondée par Richard Dermer, un résident de longue date de Montréal et propriétaire d'une petite entreprise lui-même de 1985 à 1994.",
              'Richard a obtenu un baccalauréat en arts en histoire du Collège Vassar, puis un baccalauréat en droit civil de la faculté de droit de McGill et est devenu membre du barreau du Québec en 1994.',
              "Richard a siégé au conseil d'administration et aux comités de divers organismes communautaires et a siégé au conseil d'administration et à l'exécutif de l'Association canadienne de production de disques indépendants de 2002 à 2008."], 'richard'),
            (["Administration et directrice des services à la clientèle internationale Nadine Benny travaille chez RAM Management depuis plus de 20 ans. Diplômée de l'Université Concordia en 2005 avec un baccalauréat en commerce en marketing, Nadine a participé au programme de l'Institut d'éducation coopérative où elle a effectué des stages en publicité, gestion d'artistes et dans le secteur sans but lucratif. Pianiste formée en musique classique, Nadine a complété le programme du Conservatoire de musique de McGill en 1999. Nadine parle couramment le français et l’anglais et a complété un certificat en droit à l'Université de Montréal en 2011."], 'nadine'),
            (["La coordonnatrice des clients internationaux Kayla Papps a rejoint RAM Management peu après avoir obtenu son baccalauréat en psychologie de l'Université McGill en 2019. Kayla est devenue une partie intégrante de l'équipe, maintenant les relations avec les clients et gérant les subtilités des exigences de conformité en constante évolution de l'ARC."], 'kayla'),
        ],
        'contact_title': 'CONTACT',
        'address': ['RAM Management - Avocats', '5165, chemin Queen Mary, suite 405', 'Montréal, Québec', 'Canada', 'H3W 1X7'],
        'tel': 'Tél. : 514.369.4412',
        'fax': 'Télécopieur : 514.489.5155',
        'form_label': 'Vous avez des questions?',
        'form_title': 'Communiquez avec nous',
        'map_title': 'CARTE',
        'footer_contact': 'Contact',
        'footer_pages': 'Pages',
    },
}

SERVICE_ICONS = ['waiver', 'people', 'receipt', 'search', 'court']
MAP_ADDRESS = '5165 Queen Mary Road, Montreal, QC H3W 1X7'


class Doc:
    """Collects elements for one document and gives each a stable Elementor id."""

    def __init__(self, key):
        self.key = key
        self.n = 0

    def eid(self):
        self.n += 1
        return hashlib.md5(f'{self.key}:{self.n}'.encode()).hexdigest()[:7]

    def con(self, classes, children, inner=True, tag=None, extra=None):
        s = {'content_width': 'full', 'flex_direction': 'column', 'css_classes': 'rv ' + classes}
        if tag:
            s['html_tag'] = tag
        if extra:
            s.update(extra)
        return {'id': self.eid(), 'elType': 'container', 'isInner': inner, 'settings': s, 'elements': children}

    def section(self, classes, children, bg=None, bg_y=50):
        extra = {}
        if bg:
            img_id, name = IMG[bg]
            extra = {
                'background_background': 'classic',
                'background_image': {'url': UPLOADS + name, 'id': img_id, 'size': '', 'alt': '', 'source': 'library'},
                'background_position': 'initial',
                'background_xpos': {'unit': '%', 'size': 50, 'sizes': []},
                'background_ypos': {'unit': '%', 'size': bg_y, 'sizes': []},
                'background_repeat': 'no-repeat',
                'background_size': 'cover',
            }
        return self.con('rv-section ' + classes, children, inner=False, tag='section', extra=extra)

    def w(self, wtype, classes, settings):
        s = dict(settings)
        s['_css_classes'] = classes
        return {'id': self.eid(), 'elType': 'widget', 'widgetType': wtype, 'settings': s, 'elements': []}

    def text(self, classes, html):
        return self.w('text-editor', classes, {'editor': html})

    def heading(self, classes, title, tag):
        return self.w('heading', classes, {'title': title, 'header_size': tag})

    def rule(self, classes, width):
        return self.w('divider', 'rv-rule ' + classes, {
            'width': {'unit': 'px', 'size': width, 'sizes': []},
            'weight': {'unit': 'px', 'size': 2, 'sizes': []},
            'color': '#B08D57',
            'gap': {'unit': 'px', 'size': 0, 'sizes': []},
        })

    def image(self, classes, key, alt, link=None):
        img_id, name = IMG[key]
        s = {'image': {'url': UPLOADS + name, 'id': img_id, 'alt': alt, 'source': 'library', 'size': ''},
             'image_size': 'full', 'caption_source': 'none'}
        if link:
            s['link_to'] = 'custom'
            s['link'] = {'url': link, 'is_external': '', 'nofollow': ''}
        return self.w('image', classes, s)

    @staticmethod
    def icon_value(name):
        return {'value': {'url': f'__ICON_{name}_URL__', 'id': f'__ICON_{name}_ID__'}, 'library': 'svg'}


def esc(s):
    return s.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;')


def p(s):
    return '<p>' + esc(s) + '</p>'


def email_link(user):
    return f'<p><a href="mailto:{user}@rammanagement.ca">{user}@rammanagement.ca</a></p>'


def header(lang):
    t, d = T[lang], Doc('header-' + lang)
    nav = [d.text('rv-nav-item', f'<p><a href="{url}">{esc(label)}</a></p>') for label, url in t['nav']]
    # On phones the menu folds behind the hamburger ([ram_menu_toggle] opens #ram-menu); the FR/EN switch stays beside it.
    return [d.con('rv-header', [
        d.con('rv-inner rv-header-inner', [
            d.image('rv-logo', 'logo', t['logo_alt'], link=t['nav'][0][1]),
            d.con('rv-nav', nav, tag='nav', extra={'_element_id': 'ram-menu'}),
            d.con('rv-hactions', [
                d.text('rv-lang', '<p>[ram_lang_switch style="pills"]</p>'),
                d.w('shortcode', 'rv-toggle', {'shortcode': '[ram_menu_toggle]'}),
            ]),
        ]),
    ], inner=False)]


def footer(lang):
    t, d = T[lang], Doc('footer-' + lang)
    return [d.con('rv-footer', [
        d.con('rv-inner rv-footer-inner', [
            d.con('rv-footer-cols', [
                d.con('rv-fcol rv-fbrand', [
                    d.image('rv-flogo', 'logo_white', 'R.A.M. Management', link=t['nav'][0][1]),
                    d.text('rv-ftag', p(t['home1'])),
                ]),
                d.con('rv-fcol rv-flinks', [d.heading('rv-ftitle', t['footer_pages'], 'h4')] + [d.text('rv-flink', f'<p><a href="{url}">{esc(label)}</a></p>') for label, url in t['nav']]),
                d.con('rv-fcol rv-fcontact', [
                    d.heading('rv-ftitle', t['footer_contact'], 'h4'),
                    d.text('rv-faddr', '<p>' + '<br>'.join(esc(x) for x in t['address']) + '</p>'),
                    d.text('rv-faddr', '<p>' + esc(t['tel']) + '<br>' + esc(t['fax']) + '</p>'),
                    d.text('rv-fmail', email_link('info')),
                ]),
            ]),
            d.con('rv-frule', []),
        ]),
    ], inner=False)]


def home(lang):
    t, d = T[lang], Doc('home-' + lang)
    return [d.section('rv-hero', [
        d.con('rv-inner rv-hero-inner', [
            d.text('rv-pill', p(t['home_label'])),
            d.heading('rv-h1', t['home1'].replace('RAM Management - ', 'RAM Management -<br>', 1), 'h1'),
            d.rule('rv-rule--hero', 72),
            d.text('rv-lead', p(t['home2'])),
        ]),
    ], bg='hero', bg_y=45)]


def about(lang):
    t, d = T[lang], Doc('about-' + lang)
    cards = [d.w('icon-box', 'rv-card', {
        'selected_icon': Doc.icon_value(SERVICE_ICONS[i]),
        'title_text': s,
        'description_text': '',
        'title_size': 'div',
        'position': 'top',
    }) for i, s in enumerate(t['services'])]
    members = [d.con('rv-member', [d.text('rv-member-text', p(x)) for x in paras] + [d.text('rv-member-email', email_link(user))])
               for paras, user in t['team']]
    return [
        d.section('rv-banner rv-banner--about', [
            d.con('rv-inner rv-banner-inner', [d.heading('rv-banner-title', t['about_title'], 'h1')]),
        ], bg='about_banner', bg_y=40),
        d.section('rv-intro', [
            d.image('rv-justice', 'justice', ''),
            d.con('rv-inner rv-intro-grid', [
                d.con('rv-col', [d.rule('rv-rule--short', 56), d.text('rv-intro-lead', p(t['about1']))]),
                d.con('rv-col rv-col--text', [d.text('rv-body', p(x)) for x in t['about_rest']]),
            ]),
        ]),
        d.section('rv-services', [
            d.con('rv-inner rv-services-inner', [
                d.text('rv-services-lead', p(t['about5'])),
                d.con('rv-cards', cards),
            ]),
        ]),
        d.section('rv-quote', [
            d.con('rv-inner rv-quote-inner', [
                d.text('rv-quote-mark', '<p>“</p>'),
                d.text('rv-quote-text', p(t['about6'])),
            ]),
        ], bg='quote'),
        d.section('rv-team', [
            d.con('rv-inner rv-team-inner', [
                d.text('rv-pill rv-pill--team', p(t['team_label'])),
                d.con('rv-team-grid', members),
            ]),
        ]),
    ]


def contact_form(lang):
    """Contact form section (Contact Form 7 through [ram_contact_form], which picks the form in the page's language)."""
    t, d = T[lang], Doc('contact-form-' + lang)
    return d.section('rv-form-section', [
        d.con('rv-inner rv-form-inner', [
            d.con('rv-col rv-form-intro', [
                d.text('rv-kicker', p(t['form_label'])),
                d.heading('rv-h2', t['form_title'], 'h2'),
            ]),
            d.con('rv-form-card', [d.w('shortcode', 'rv-form', {'shortcode': '[ram_contact_form]'})]),
        ]),
    ])


def contact(lang):
    t, d = T[lang], Doc('contact-' + lang)

    def card(icon, html, link=None, extra_cls=''):
        extra = {}
        tag = None
        if link:
            tag = 'a'
            extra = {'link': {'url': link, 'is_external': '', 'nofollow': ''}}
        return d.con('rv-dcard', [
            d.w('icon', 'rv-dicon', {'selected_icon': Doc.icon_value(icon)}),
            d.text('rv-dtext ' + extra_cls, html),
        ], tag=tag, extra=extra)

    return [
        d.section('rv-banner rv-banner--contact', [
            d.con('rv-inner rv-banner-inner', [d.heading('rv-banner-title', t['contact_title'], 'h1')]),
        ], bg='contact_banner'),
        d.section('rv-details', [
            d.con('rv-inner rv-details-inner', [
                d.con('rv-dcards', [
                    card('pin', '<p>' + '<br>'.join(esc(x) for x in t['address']) + '</p>'),
                    card('phone', '<p>' + esc(t['tel']) + '<br>' + esc(t['fax']) + '</p>', link='tel:+15143694412'),
                    card('mail', '<p>info@rammanagement.ca</p>', link='mailto:info@rammanagement.ca', extra_cls='rv-dtext--mail'),
                ]),
            ]),
        ]),
        # The contact form section (contact_form) was taken off the Contact pages at the client's request.
        d.section('rv-map-section', [
            d.con('rv-inner rv-map-inner', [
                d.heading('rv-h2', t['map_title'], 'h2'),
                d.w('google_maps', 'rv-map', {
                    'address': MAP_ADDRESS,
                    'zoom': {'unit': 'px', 'size': 15, 'sizes': []},
                    'height': {'unit': 'px', 'size': 440, 'sizes': []},
                }),
            ]),
        ]),
    ]


def main():
    out = os.path.join(HERE, 'elementor')
    os.makedirs(out, exist_ok=True)
    for name, fn in (('header', header), ('footer', footer), ('home', home), ('about', about), ('contact', contact), ('contact-form', lambda lang: [contact_form(lang)])):
        for lang in ('fr', 'en'):
            with open(os.path.join(out, f'{name}-{lang}.json'), 'w', encoding='utf-8') as f:
                json.dump(fn(lang), f, ensure_ascii=False, separators=(',', ':'))
                f.write('\n')


if __name__ == '__main__':
    main()
