<?php
$page_title = "About Us";
include 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="section-title">
            <h2>About Eco-Sphere</h2>
            <p>Growing a greener future, one plant at a time</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; align-items: center; margin-bottom: 4rem;">
            <div>
                <img src="images\Eco-Sphere.png" alt="About Eco-Sphere" style="width: 100%; border-radius: 8px; box-shadow: var(--shadow);">
            </div>
            <div>
                <h3>Our Mission</h3>
                <p>At Eco-Sphere, we believe in the transformative power of plants. Our mission is to make green living accessible to everyone by providing high-quality, eco-friendly plants and trees while promoting sustainable practices and environmental awareness.</p>
                
                <h3 style="margin-top: 2rem;">Our Story</h3>
                <p>Founded in 2015, Eco-Sphere started as a small community initiative to promote urban gardening. What began as a passion project among friends has grown into a trusted destination for plant enthusiasts and eco-conscious consumers alike.</p>
            </div>
        </div>

        <div style="background: var(--light-green); padding: 3rem; border-radius: 8px; margin-bottom: 4rem;">
            <h3 style="text-align: center; margin-bottom: 2rem;">Why Choose Eco-Sphere?</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div style="text-align: center;">
                    <div style="background: var(--primary-green); color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="fas fa-leaf" style="font-size: 1.5rem;"></i>
                    </div>
                    <h4>Quality Plants</h4>
                    <p>We source only the healthiest plants from sustainable growers and nurseries.</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="background: var(--primary-green); color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="fas fa-shield-alt" style="font-size: 1.5rem;"></i>
                    </div>
                    <h4>Expert Guidance</h4>
                    <p>Our team of plant experts provides personalized advice for your green space.</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="background: var(--primary-green); color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="fas fa-truck" style="font-size: 1.5rem;"></i>
                    </div>
                    <h4>Safe Delivery</h4>
                    <p>We ensure your plants arrive in perfect condition with our specialized packaging.</p>
                </div>
                
                <div style="text-align: center;">
                    <div style="background: var(--primary-green); color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem;">
                        <i class="fas fa-heart" style="font-size: 1.5rem;"></i>
                    </div>
                    <h4>Eco-Friendly</h4>
                    <p>We're committed to sustainable practices and environmental conservation.</p>
                </div>
            </div>
        </div>

        <div style="margin-bottom: 4rem;">
            <h3 style="text-align: center; margin-bottom: 2rem;">Our Team</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem;">
                <div style="text-align: center;">
                    <img src="images\Team Member\Rakib.jpg" alt="Team Member" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h4>Md. Rakibul Hasan</h4>
                    <p style="color: var(--primary-green); font-weight: bold;">Founder & CEO</p>
                    <p>Botany expert with 15+ years of experience in sustainable agriculture.</p>
                </div>
                
                <div style="text-align: center;">
                    <img src="images\Team Member\Arfin.jpg" alt="Team Member" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h4>Md. Shamsul Arfin Sarker</h4>
                    <p style="color: var(--primary-green); font-weight: bold;">Head Horticulturist</p>
                    <p>Specializes in indoor plants and urban gardening solutions.</p>
                </div>
                
                <div style="text-align: center;">
                    <img src="images\Team Member\Shahariar.jpg" alt="Team Member" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; margin-bottom: 1rem;">
                    <h4>Md. Shahariar Kabir Shihab</h4>
                    <p style="color: var(--primary-green); font-weight: bold;">Customer Care Manager</p>
                    <p>Dedicated to ensuring every customer has the best plant experience.</p>
                </div>
            </div>
        </div>

        <div style="background: var(--dark-gray); color: white; padding: 3rem; border-radius: 8px; text-align: center;">
            <h3 style="margin-bottom: 1rem;">Join Our Green Community</h3>
            <p style="margin-bottom: 2rem;">Be part of our mission to make the world greener. Follow us on social media for tips, updates, and special offers.</p>
            <div class="social-icons" style="justify-content: center;">
                <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-instagram"></i></a>
                <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-twitter"></i></a>
                <a href="https://www.facebook.com/rsrakibulhasan.rakib.79"><i class="fab fa-pinterest"></i></a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>