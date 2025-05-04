<!--
    View file for the About Us page.
    Displays static information about the company: its story, mission, values, and team.
    This page primarily uses HTML for structure and content presentation.
    No dynamic PHP variables are expected for the main content, it's purely informational.
-->
<main>
    <!-- Main section container for the about page content -->
    <section class="about-section">
        <div class="container">
            <!-- Page Title Block -->
            <div class="page-title">
                <h2>About GhibliGroceries</h2>
                <p>Our journey, mission, and values</p>
            </div>
            <!-- Wrapper for the main content sections -->
            <div class="about-content">
                <!-- Introduction Section: Story and Mission -->
                <section class="about-intro">
                    <h3>Our Story</h3>
                    <p>GhibliGroceries was founded in 2020 with a simple yet powerful vision: to make fresh,
                        high-quality groceries accessible to everyone. What started as a small neighborhood store has
                        grown into a trusted name in the community.</p>
                    <h3>Our Mission</h3>
                    <p>We believe that good food is the foundation of a good life. Our mission is to provide fresh,
                        sustainably sourced products that nourish both people and the planet.</p>
                </section>
                <!-- Values Section -->
                <section class="about-values">
                    <h3>Our Values</h3>
                    <!-- Grid layout for displaying company values -->
                    <div class="values-grid">
                        <!-- Value Item: Quality -->
                        <article class="value-item">
                            <i class="fas fa-leaf"></i> <!-- Icon representing Quality -->
                            <h4>Quality</h4>
                            <p>We never compromise on quality. Our products are carefully selected to ensure they meet
                                our high standards.</p>
                        </article>
                        <!-- Value Item: Community -->
                        <article class="value-item">
                            <i class="fas fa-heart"></i> <!-- Icon representing Community -->
                            <h4>Community</h4>
                            <p>We're proud to be part of the community we serve and are committed to giving back through
                                local initiatives.</p>
                        </article>
                        <!-- Value Item: Sustainability -->
                        <article class="value-item">
                            <i class="fas fa-globe"></i> <!-- Icon representing Sustainability -->
                            <h4>Sustainability</h4>
                            <p>We're dedicated to sustainable practices, from how we source our products to how we
                                package them.</p>
                        </article>
                        <!-- Value Item: Integrity -->
                        <article class="value-item">
                            <i class="fas fa-handshake"></i> <!-- Icon representing Integrity -->
                            <h4>Integrity</h4>
                            <p>We operate with transparency and honesty in all that we do, earning the trust of our
                                customers every day.</p>
                        </article>
                    </div>
                </section>
                <!-- Team Section -->
                <section class="about-team">
                    <h3>Our Team</h3>
                    <p>Behind GhibliGroceries is a team of passionate individuals who share a common goal: to make
                        quality food accessible to all. From our store associates to our management team, everyone plays
                        a vital role in bringing our vision to life.</p>
                </section>
            </div> <!-- End of about-content -->
        </div> <!-- End of container -->
    </section> <!-- End of about-section -->
</main> <!-- End of main -->