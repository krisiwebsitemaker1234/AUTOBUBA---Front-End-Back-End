<?php
include_once 'config.php';

// Get the blog post slug from URL
$slug = $_GET['slug'] ?? '';

// Blog posts data (in a real application, this would come from a database)
$blog_posts = [
    'top-10-electric-vehicles-2025' => [
        'id' => 1,
        'title' => 'Top 10 Electric Vehicles to Watch in 2025',
        'slug' => 'top-10-electric-vehicles-2025',
        'date' => 'January 15, 2025',
        'comments' => 12,
        'image' => 'https://images.pexels.com/photos/110844/pexels-photo-110844.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'category' => 'Electric Vehicles',
        'tags' => ['Electric Cars', 'Tesla', 'BMW', 'Future Tech', '2025'],
        'content' => [
            'intro' => 'The electric vehicle revolution is accelerating, and 2025 promises to be a landmark year for EV enthusiasts. From luxury sedans to affordable family SUVs, manufacturers are pushing the boundaries of what\'s possible with electric powertrains.',
            'sections' => [
                [
                    'heading' => '1. Tesla Model S Plaid+',
                    'content' => 'Tesla continues to lead the pack with the Model S Plaid+, featuring a tri-motor setup that delivers over 1,100 horsepower and a range exceeding 520 miles. The updated interior includes a 17-inch touchscreen and enhanced autopilot capabilities.',
                    'image' => 'https://images.pexels.com/photos/26957121/pexels-photo-26957121/free-photo-of-tesla.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => '2. BMW iX M60',
                    'content' => 'BMW\'s flagship electric SUV combines luxury with performance. The iX M60 boasts 610 horsepower, all-wheel drive, and BMW\'s latest iDrive 8 infotainment system. Its minimalist interior design sets new standards for premium EVs.',
                    'image' => 'https://images6.alphacoders.com/128/thumb-1920-1282279.jpg'
                ],
                [
                    'heading' => '3. Mercedes EQS AMG',
                    'content' => 'The EQS AMG represents Mercedes\' commitment to electric luxury. With its HYPERSCREEN dashboard spanning the entire width of the cabin and over 450 miles of range, it redefines what an electric luxury sedan can be.',
                    'image' => 'https://images.unsplash.com/photo-1623013438264-d176fb91ee99?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'
                ],
                [
                    'heading' => '4. Ford F-150 Lightning Pro',
                    'content' => 'America\'s best-selling truck goes electric with impressive capabilities. The Lightning Pro offers 563 horsepower, can power your home for up to 10 days, and maintains the rugged utility that made the F-150 legendary.',
                    'image' => 'https://images.unsplash.com/photo-1686715018049-f73970aa97d3?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'
                ],
                [
                    'heading' => '5. Audi E-Tron GT RS',
                    'content' => 'Audi\'s answer to the Porsche Taycan delivers 637 horsepower and stunning design. The T-tron GT RS can accelerate from 0-60 mph in just 3.1 seconds while maintaining Audi\'s signature luxury and build quality.',
                    'image' => 'https://images.unsplash.com/photo-1655126276417-a2427cc1ba20?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'
                ]
            ],
            'conclusion' => 'These vehicles represent just the beginning of what 2025 has to offer. As battery technology continues to improve and charging infrastructure expands, electric vehicles are becoming more practical and appealing to mainstream consumers. The future of transportation is electric, and it\'s arriving faster than ever.'
        ]
    ],
    'car-maintenance-tips-winter' => [
        'id' => 2,
        'title' => 'Essential Car Maintenance Tips for Winter',
        'slug' => 'car-maintenance-tips-winter',
        'date' => 'January 12, 2025',
        'comments' => 8,
        'image' => 'https://images.pexels.com/photos/14472323/pexels-photo-14472323.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'category' => 'Maintenance',
        'tags' => ['Winter Driving', 'Maintenance', 'Safety', 'Tips', 'Cold Weather'],
        'content' => [
            'intro' => 'Winter weather can be harsh on your vehicle. Proper maintenance during the cold months is crucial for safety, reliability, and extending your car\'s lifespan. Here are the essential steps every driver should take.',
            'sections' => [
                [
                    'heading' => 'Check Your Battery',
                    'content' => 'Cold weather reduces battery capacity by up to 50%. Have your battery tested before winter arrives, and clean any corrosion from terminals. If your battery is more than 3 years old, consider replacement.',
                    'image' => 'https://images.pexels.com/photos/1409999/pexels-photo-1409999.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Inspect Your Tires',
                    'content' => 'Tire pressure drops 1-2 PSI for every 10°F decrease in temperature. Check pressure monthly and consider winter tires if you live in areas with heavy snow. Ensure tread depth is at least 4/32 inches.',
                    'image' => 'https://images.pexels.com/photos/21694/pexels-photo.jpg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Fluid Levels and Quality',
                    'content' => 'Check antifreeze levels and concentration. Ensure windshield washer fluid is rated for freezing temperatures. Consider switching to winter-grade oil if recommended by your manufacturer.',
                    'image' => 'https://images.pexels.com/photos/6870297/pexels-photo-6870297.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Emergency Kit Essentials',
                    'content' => 'Pack an emergency kit with blankets, flashlight, jumper cables, ice scraper, sand or cat litter for traction, and non-perishable snacks. Keep your gas tank at least half full.',
                    'image' => 'https://images.pexels.com/photos/249348/pexels-photo-249348.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ]
            ],
            'conclusion' => 'Taking these preventive measures will help ensure your vehicle performs reliably throughout the winter months. Regular maintenance not only keeps you safe but also prevents costly repairs down the road.'
        ]
    ],
    'hybrid-vs-electric-comparison' => [
        'id' => 3,
        'title' => 'Hybrid vs Electric: Which is Right for You?',
        'slug' => 'hybrid-vs-electric-comparison',
        'date' => 'January 10, 2025',
        'comments' => 15,
        'image' => 'https://images.pexels.com/photos/12353734/pexels-photo-12353734.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'category' => 'Buying Guide',
        'tags' => ['Hybrid', 'Electric', 'Comparison', 'Buying Guide', 'Fuel Economy'],
        'content' => [
            'intro' => 'Choosing between a hybrid and electric vehicle can be challenging. Both offer environmental benefits and fuel savings, but they serve different needs and lifestyles. Let\'s break down the key differences to help you make an informed decision.',
            'sections' => [
                [
                    'heading' => 'How They Work',
                    'content' => 'Hybrid vehicles combine a gasoline engine with an electric motor, automatically switching between or combining both power sources. Electric vehicles run solely on battery power, requiring regular charging from external sources.',
                    'image' => 'https://images.pexels.com/photos/5733709/pexels-photo-5733709.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Range and Refueling',
                    'content' => 'Hybrids offer unlimited range with gas stations everywhere. Most get 40-60 MPG. Electric vehicles typically offer 200-400 miles per charge, but require planning around charging stations for long trips.',
                    'image' => 'https://images.pexels.com/photos/9799995/pexels-photo-9799995.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Cost Considerations',
                    'content' => 'Hybrids generally cost less upfront and have lower maintenance costs. Electric vehicles have higher purchase prices but qualify for federal tax credits up to $7,500 and have minimal maintenance requirements.',
                    'image' => 'https://images.pexels.com/photos/9799735/pexels-photo-9799735.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Environmental Impact',
                    'content' => 'Both reduce emissions compared to conventional vehicles. Hybrids cut emissions by 25-35%, while electric vehicles produce zero direct emissions. The environmental benefit of EVs depends on your local electricity grid.',
                    'image' => 'https://images.pexels.com/photos/4895432/pexels-photo-4895432.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ]
            ],
            'conclusion' => 'Choose a hybrid if you want fuel savings without changing your driving habits, take frequent long trips, or live in an area with limited charging infrastructure. Choose electric if you primarily drive locally, have access to home charging, and want the latest technology with minimal maintenance.'
        ]
    ],
    'future-autonomous-driving' => [
        'id' => 4,
        'title' => 'The Future of Autonomous Driving Technology',
        'slug' => 'future-autonomous-driving',
        'date' => 'January 8, 2025',
        'comments' => 22,
        'image' => 'https://images.pexels.com/photos/17791131/pexels-photo-17791131/free-photo-of-steering-wheel-in-a-car.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'category' => 'Technology',
        'tags' => ['Autonomous', 'AI', 'Future Tech', 'Safety', 'Innovation'],
        'content' => [
            'intro' => 'Autonomous driving technology is rapidly evolving, promising to revolutionize transportation. From current driver assistance features to fully self-driving vehicles, let\'s explore what the future holds.',
            'sections' => [
                [
                    'heading' => 'Current State of Technology',
                    'content' => 'Today\'s vehicles offer Level 2 automation with features like adaptive cruise control, lane keeping assist, and automatic emergency braking. Companies like Tesla, Waymo, and Cruise are testing Level 4 systems in limited areas.',
                    'image' => 'https://images.pexels.com/photos/2526127/pexels-photo-2526127.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Levels of Automation',
                    'content' => 'SAE defines 6 levels (0-5) of driving automation. Level 3 allows hands-off driving in specific conditions, Level 4 is fully autonomous in defined areas, and Level 5 represents complete automation anywhere.',
                    'image' => 'https://images.pexels.com/photos/30112419/pexels-photo-30112419/free-photo-of-close-up-of-a-modern-sports-car-gear-shift.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Safety and Benefits',
                    'content' => 'Autonomous vehicles could prevent 94% of serious traffic crashes caused by human error. They also promise improved mobility for elderly and disabled individuals, reduced traffic congestion, and more efficient transportation.',
                    'image' => 'https://images.pexels.com/photos/9952332/pexels-photo-9952332.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Challenges Ahead',
                    'content' => 'Technical challenges include handling edge cases, weather conditions, and complex urban environments. Regulatory frameworks, public acceptance, and cybersecurity concerns also need to be addressed.',
                    'image' => 'https://images.pexels.com/photos/228094/pexels-photo-228094.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ]
            ],
            'conclusion' => 'While fully autonomous vehicles may still be years away for widespread adoption, the technology continues to advance rapidly. The transition will likely be gradual, with increasing levels of automation becoming standard in new vehicles over the next decade.'
        ]
    ],
    'best-family-suvs-under-50k' => [
        'id' => 5,
        'title' => 'Best Family SUVs Under $50,000',
        'slug' => 'best-family-suvs-under-50k',
        'date' => 'January 5, 2025',
        'comments' => 18,
        'image' => 'https://images.pexels.com/photos/9598104/pexels-photo-9598104.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'category' => 'Reviews',
        'tags' => ['SUV', 'Family Cars', 'Reviews', 'Safety', 'Value'],
        'content' => [
            'intro' => 'Finding the perfect family SUV that balances safety, space, features, and value can be challenging. Here are our top picks for family SUVs under $50,000 that excel in all these areas.',
            'sections' => [
                [
                    'heading' => '1. Honda Pilot',
                    'content' => 'The Honda Pilot offers three rows of seating for up to 8 passengers, excellent safety ratings, and Honda\'s reputation for reliability. Starting at $38,000, it includes Honda Sensing safety suite standard.',
                    'image' => 'https://images.unsplash.com/photo-1742997742821-3b9c571b6571?q=80&w=2057&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'
                ],
                [
                    'heading' => '2. Toyota Highlander',
                    'content' => 'Known for reliability and resale value, the Highlander provides comfortable seating for 8, standard all-wheel drive, and Toyota Safety Sense 2.0. Hybrid models offer excellent fuel economy.',
                    'image' => 'https://images.pexels.com/photos/30486410/pexels-photo-30486410/free-photo-of-toyota-suv-in-oregon-desert-landscape.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => '3. Mazda CX-9',
                    'content' => 'The CX-9 stands out with its upscale interior, engaging driving dynamics, and attractive styling. While it seats 7, the premium feel and features make it excellent value at its price point.',
                    'image' => 'https://images.pexels.com/photos/26224702/pexels-photo-26224702/free-photo-of-a-black-mazda-cx-50-parked-in-front-of-a-building-in-a-city.jpeg?auto=compress&cs=tinysrgb&w=600'
                ],
                [
                    'heading' => '4. Subaru Ascent',
                    'content' => 'Standard all-wheel drive, excellent safety ratings, and generous cargo space make the Ascent ideal for families. The EyeSight driver assistance system comes standard across all trim levels.',
                    'image' => 'https://images.pexels.com/photos/5158083/pexels-photo-5158083.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ]
            ],
            'conclusion' => 'Each of these SUVs offers unique strengths for families. Consider your priorities - whether it\'s maximum seating, fuel economy, luxury features, or all-weather capability - to choose the best fit for your family\'s needs.'
        ]
    ],
    'increase-car-resale-value' => [
        'id' => 6,
        'title' => 'How to Increase Your Car\'s Resale Value',
        'slug' => 'increase-car-resale-value',
        'date' => 'January 3, 2025',
        'comments' => 9,
        'image' => 'https://images.pexels.com/photos/5800943/pexels-photo-5800943.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2',
        'category' => 'Tips',
        'tags' => ['Resale Value', 'Car Care', 'Investment', 'Maintenance', 'Tips'],
        'content' => [
            'intro' => 'Your car is likely one of your largest investments. While depreciation is inevitable, there are proven strategies to maximize your vehicle\'s resale value when it\'s time to sell or trade in.',
            'sections' => [
                [
                    'heading' => 'Maintain Service Records',
                    'content' => 'Keep detailed records of all maintenance and repairs. Regular oil changes, scheduled services, and addressing issues promptly show potential buyers that the vehicle has been well cared for.',
                    'image' => 'https://images.pexels.com/photos/7564871/pexels-photo-7564871.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Protect the Interior and Exterior',
                    'content' => 'Use seat covers, floor mats, and window tinting to protect the interior. Regular washing, waxing, and paint protection help maintain the exterior. Address scratches and dents promptly.',
                    'image' => 'https://images.pexels.com/photos/9258341/pexels-photo-9258341.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Choose Popular Options',
                    'content' => 'When buying, select popular colors (white, black, silver) and desirable options like all-wheel drive, navigation, and safety features. These typically have better resale value than unique or unusual choices.',
                    'image' => 'https://images.pexels.com/photos/17519357/pexels-photo-17519357/free-photo-of-silver-toyota-4-runner.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ],
                [
                    'heading' => 'Time Your Sale Right',
                    'content' => 'Sell before major repairs are needed, typically before 100,000 miles. Consider seasonal factors - convertibles sell better in spring, while SUVs are popular before winter.',
                    'image' => 'https://images.pexels.com/photos/164634/pexels-photo-164634.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2'
                ]
            ],
            'conclusion' => 'Maximizing resale value starts the day you buy your car. With proper care, maintenance, and strategic timing, you can significantly reduce the total cost of ownership and get the best return on your automotive investment.'
        ]
    ]
];

// Get the specific blog post
$post = $blog_posts[$slug] ?? null;

if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Related posts (excluding current post)
$related_posts = array_filter($blog_posts, function($p) use ($slug) {
    return $p['slug'] !== $slug;
});
$related_posts = array_slice($related_posts, 0, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - AUTOBUBA Blog</title>
    <meta name="description" content="<?= htmlspecialchars($post['content']['intro']) ?>">
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700;900&display=swap" rel="stylesheet">
    
    <!-- CSS Styles -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/css/flag-icon.min.css">
    <script src="https://kit.fontawesome.com/dfba5e2447.js" crossorigin="anonymous"></script>
    
    <style>
        :root {
            --primary-color: #dc3545;
            --secondary-color: #2d3436;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --white-color: #ffffff;
        }

        .blog-post-hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('img/blog/blog-hero-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            color: white;
            text-align: center;
        }

        .blog-post-content {
            padding: 60px 0;
        }

        .blog-post-header {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #eee;
        }

        .blog-post-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
            color: var(--gray-color);
            font-size: 14px;
        }

        .blog-post-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .blog-post-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .blog-post-category {
            background: var(--primary-color);
            color: white;
            padding: 6px 15px;
            border-radius: 25px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            margin-bottom: 20px;
        }

        .blog-post-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .blog-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--dark-color);
        }

        .blog-content p {
            margin-bottom: 25px;
        }

        .blog-content h3 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 40px 0 20px 0;
            padding-left: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .blog-content img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            margin: 30px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .blog-intro {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--gray-color);
            font-style: italic;
            margin-bottom: 40px;
            padding: 20px;
            background: var(--light-color);
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .blog-conclusion {
            background: var(--light-color);
            padding: 30px;
            border-radius: 12px;
            margin-top: 40px;
            border-left: 4px solid var(--primary-color);
        }

        .author-bio {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin: 40px 0;
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
        }

        .author-info h4 {
            margin-bottom: 10px;
            color: var(--dark-color);
        }

        .author-info p {
            color: var(--gray-color);
            margin: 0;
        }

        .blog-tags {
            margin: 30px 0;
        }

        .blog-tag {
            display: inline-block;
            background: var(--light-color);
            color: var(--dark-color);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin: 5px 10px 5px 0;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .blog-tag:hover {
            background: var(--primary-color);
            color: white;
        }

        .related-posts {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 2px solid #eee;
        }

        .related-post-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            margin-bottom: 30px;
        }

        .related-post-item:hover {
            transform: translateY(-5px);
        }

        .related-post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .related-post-content {
            padding: 20px;
        }

        .related-post-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .related-post-title a {
            color: var(--dark-color);
            text-decoration: none;
        }

        .related-post-title a:hover {
            color: var(--primary-color);
        }

        .related-post-meta {
            font-size: 14px;
            color: var(--gray-color);
        }

        .share-buttons {
            margin: 40px 0;
            text-align: center;
        }

        .share-btn {
            display: inline-block;
            padding: 12px 20px;
            margin: 0 10px;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            font-weight: 600;
            transition: transform 0.3s ease;
        }

        .share-btn:hover {
            transform: translateY(-2px);
            color: white;
        }

        .share-facebook { background: #3b5998; }
        .share-twitter { background: #1da1f2; }
        .share-linkedin { background: #0077b5; }
        .share-email { background: var(--gray-color); }

        @media (max-width: 768px) {
            .blog-post-title {
                font-size: 2rem;
            }
            
            .blog-content {
                font-size: 1rem;
            }
            
            .author-bio {
                flex-direction: column;
                text-align: center;
            }
            
            .blog-post-meta {
                justify-content: center;
            }
        }
    </style>
</head>

<body>
    <!-- Header Section Begin -->
    <header class="header">
        <nav class="navbar navbar-expand-lg navbar-light bg-white">
            <div class="container">
                <a class="navbar-brand" href="./index.php">
                    <img src="img/logo zoom.png" alt="AUTOBUBA" class="img-fluid" style="max-height: 60px;">
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav mx-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="car.php">Cars</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="blog.php">Blog</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about.php">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="work.php">Work</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <!-- Blog Post Hero -->
    <section class="blog-post-hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <span class="blog-post-category"><?= $post['category'] ?></span>
                    <h1 style="color:white;" class="blog-post-title"><?= htmlspecialchars($post['title']) ?></h1>
                    <div class="blog-post-meta">
                        <span style="color :white;"><i class="fa fa-calendar"></i> <?= $post['date'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Blog Post Content -->
    <section class="blog-post-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <img src="<?= $post['image'] ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="blog-post-image">
                    
                    <div class="blog-content">
                        <div class="blog-intro">
                            <?= htmlspecialchars($post['content']['intro']) ?>
                        </div>

                        <?php foreach($post['content']['sections'] as $section): ?>
                            <h3><?= htmlspecialchars($section['heading']) ?></h3>
                            <p><?= htmlspecialchars($section['content']) ?></p>
                            <?php if(isset($section['image'])): ?>
                                <img src="<?= $section['image'] ?>" alt="<?= htmlspecialchars($section['heading']) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <div class="blog-conclusion">
                            <h3>Conclusion</h3>
                            <p><?= htmlspecialchars($post['content']['conclusion']) ?></p>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="blog-tags mt-10">
                        <h4>Tags:</h4>
                        <?php foreach($post['tags'] as $tag): ?>
                            <a href="#" class="blog-tag"><?= htmlspecialchars($tag) ?></a>
                        <?php endforeach; ?>
                    </div>


                    <!-- Related Posts -->
                    <div class="related-posts">
                        <h3>Related Articles</h3>
                        <div class="row mt-4">
                            <?php foreach($related_posts as $related): ?>
                            <div class="col-md-4">
                                <div class="related-post-item">
                                    <img src="<?= $related['image'] ?>" alt="<?= htmlspecialchars($related['title']) ?>" class="related-post-image">
                                    <div class="related-post-content">
                                        <h5 class="related-post-title">
                                            <a href="blog-post.php?slug=<?= $related['slug'] ?>"><?= htmlspecialchars($related['title']) ?></a>
                                        </h5>
                                        <div class="related-post-meta">
                                            <i class="fa fa-calendar"></i> <?= $related['date'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section Begin -->
<footer class="footer set-bg" style="background: url(img/footer-bg.jpg);">
    <div class="container">
        <div class="row pt-5">
            <!-- Logo and Contact Section -->
            <div class="col-lg-4 col-md-6 col-12 mb-4 mb-lg-0">
                <div class="footer__about">
                    <div class="footer__logo">
                        <img src="img/logo zoom.png" alt="Autobuba Logo" class="img-fluid">
                    </div>
                    <div class="footer__contact-box">
                        <p><i class="fa fa-envelope"></i> autobuba2025@gmail.com</p>
                        <p><i class="fa fa-phone"></i> +355 69 311 3209</p>
                    </div>
                    <div class="footer__social">
                        <a href="https://www.tiktok.com/@autobuba1" class="social-btn tiktok" target="_blank">
                            <i class="fa-brands fa-tiktok"></i>
                        </a>
                        <a href="https://www.instagram.com/auto_buba?igsh=MWg1b213Y3oxeG1ydg=="
                            class="social-btn instagram" target="_blank">
                            <i class="fa-brands fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Navigation and Learn Sections - Side by Side on Mobile -->
            <div class="col-lg-8 col-md-6 col-12">
                <div class="row">
                    <!-- Navigation Section -->
                    <div class="col-6">
                        <div class="footer__widget">
                            <h5 data-translate="Navigation">Navigation</h5>
                            <ul class="list-unstyled footer-links">
                                <li><a href="contact.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="Contact Us">Contact Us</span>
                                </a></li>
                                <li><a href="car.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Cars">Cars</span>
                                </a></li>
                                <li><a href="blog.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Blog">Blog</span>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Learn Section -->
                    <div class="col-6">
                        <div class="footer__brand">
                            <h5 data-translate="Learn">Learn</h5>
                            <ul class="list-unstyled footer-links">
                                <li><a href="about.php" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="About Us">About Us</span>
                                </a></li>
                                <li><a href="#chooseUs" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i> 
                                    <span data-translate="Why Us?">Why Us?</span>
                                </a></li>
                                <li><a href="#trustedName" class="text-decoration-none">
                                    <i class="fa fa-angle-right"></i>
                                    <span data-translate="Who are we?">Who are we?</span>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer__bottom">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="copyright" data-translate="Copyright">
                        © Copyright 2025 QUOLYTECH. All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Footer Section End -->

    <!-- Scripts -->
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
